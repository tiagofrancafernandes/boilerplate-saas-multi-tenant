import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useInfraOps } from '../useInfraOps';

describe('useInfraOps Composable', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it('happy path: allows access when user has super-admin role', () => {
        const { canAccessInfra } = useInfraOps({
            userRole: 'super-admin',
            permissions: [],
        });

        expect(canAccessInfra.value).toBe(true);
    });

    it('happy path: allows access when user has run-scheduler permission', () => {
        const { canAccessInfra } = useInfraOps({
            userRole: 'manager',
            permissions: ['run-scheduler'],
        });

        expect(canAccessInfra.value).toBe(true);
    });

    it('sad path: blocks access when user lacks super-admin role and permission', () => {
        const { canAccessInfra, triggerScheduler } = useInfraOps({
            userRole: 'tenant-user',
            permissions: ['read-posts'],
        });

        expect(canAccessInfra.value).toBe(false);
    });

    it('sad path: returns forbidden result if executeRequest is called without permission', async () => {
        const { triggerScheduler, lastResult } = useInfraOps({
            userRole: 'tenant-user',
            permissions: [],
        });

        const result = await triggerScheduler();

        expect(result.success).toBe(false);
        expect(result.exitCode).toBe(403);
        expect(lastResult.value?.error).toBe('Forbidden');
    });

    it('happy path: triggerScheduler sends static header and returns formatted output', async () => {
        const mockFetch = vi.fn().mockResolvedValue({
            ok: true,
            status: 200,
            json: async () => ({
                success: true,
                message: 'Scheduler executed successfully.',
                data: {
                    success: true,
                    exit_code: 0,
                    output: 'Running scheduled tasks...',
                    executed_at: '2026-09-19T14:00:00Z',
                },
            }),
        });
        global.fetch = mockFetch;

        const { triggerScheduler, lastResult, history } = useInfraOps({
            apiBaseUrl: 'http://localhost:8000',
            staticHeaderKey: 'my-infra-secret',
            headerName: 'X-Infra-Key',
        });

        const result = await triggerScheduler();

        expect(mockFetch).toHaveBeenCalledWith('http://localhost:8000/api/infra/scheduler', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Infra-Key': 'my-infra-secret',
            },
            body: JSON.stringify({ webhooks: undefined }),
        });

        expect(result.success).toBe(true);
        expect(result.exitCode).toBe(0);
        expect(result.output).toBe('Running scheduled tasks...');
        expect(lastResult.value).toEqual(result);
        expect(history.value.length).toBe(1);
    });

    it('happy path: runSubscriptionCheck passes tenant parameter and artisan command', async () => {
        const mockFetch = vi.fn().mockResolvedValue({
            ok: true,
            status: 200,
            json: async () => ({
                success: true,
                message: 'Command executed',
                data: {
                    success: true,
                    exit_code: 0,
                    output: 'Tenant checked',
                },
            }),
        });
        global.fetch = mockFetch;

        const { runSubscriptionCheck } = useInfraOps({
            apiBaseUrl: 'http://localhost:8000',
            bearerToken: 'super-admin-sanctum-token',
        });

        const result = await runSubscriptionCheck('tenant-123');

        expect(mockFetch).toHaveBeenCalledWith(
            'http://localhost:8000/api/infra/artisan',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({
                    command: 'subscriptions:check-cycles',
                    parameters: { '--tenant': 'tenant-123' },
                    webhooks: undefined,
                }),
            })
        );

        expect(result.success).toBe(true);
    });

    it('sad path: handles HTTP error responses cleanly without unhandled rejection', async () => {
        const mockFetch = vi.fn().mockResolvedValue({
            ok: false,
            status: 404,
            statusText: 'Not Found',
            json: async () => ({
                message: 'Not Found',
            }),
        });
        global.fetch = mockFetch;

        const { runArtisan, lastResult } = useInfraOps();

        const result = await runArtisan('unknown-command');

        expect(result.success).toBe(false);
        expect(result.exitCode).toBe(404);
        expect(result.message).toBe('Not Found');
        expect(lastResult.value?.success).toBe(false);
    });

    it('sad path: handles network fetch exception safely', async () => {
        global.fetch = vi.fn().mockRejectedValue(new Error('Connection failed'));

        const { runQueueWorker, lastResult } = useInfraOps();

        const result = await runQueueWorker();

        expect(result.success).toBe(false);
        expect(result.message).toBe('Connection failed');
        expect(lastResult.value?.error).toBe('Connection failed');
    });
});
