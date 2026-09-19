import { computed, ref } from 'vue';

export interface InfraExecutionResult {
    success: boolean;
    message: string;
    exitCode: number;
    output: string;
    executedAt: string;
    durationMs: number;
    endpoint: string;
    raw?: any;
    error?: string;
}

export interface WebhookConfig {
    url: string;
    method?: string;
    headers?: Record<string, string>;
    body?: Record<string, any>;
}

export interface WebhooksPayload {
    success?: WebhookConfig;
    error?: WebhookConfig;
    final?: WebhookConfig;
}

export interface InfraOpsOptions {
    apiBaseUrl?: string;
    staticHeaderKey?: string;
    headerName?: string;
    bearerToken?: string;
    userRole?: string;
    permissions?: string[];
}

export function useInfraOps(initialOptions: InfraOpsOptions = {}) {
    const apiBaseUrl = ref<string>(initialOptions.apiBaseUrl ?? 'http://localhost:8000');
    const staticHeaderKey = ref<string>(initialOptions.staticHeaderKey ?? 'change-this-to-a-secure-random-token');
    const headerName = ref<string>(initialOptions.headerName ?? 'X-Infra-Key');
    const bearerToken = ref<string>(initialOptions.bearerToken ?? '');
    const userRole = ref<string>(initialOptions.userRole ?? 'super-admin');
    const permissions = ref<string[]>(initialOptions.permissions ?? ['run-scheduler', 'manage-infra']);

    const loading = ref<boolean>(false);
    const lastResult = ref<InfraExecutionResult | null>(null);
    const history = ref<InfraExecutionResult[]>([]);

    /**
     * Authorization guard:
     * Only users with super-admin role or designated infra permissions are authorized.
     */
    const canAccessInfra = computed<boolean>(() => {
        if (userRole.value === 'super-admin') {
            return true;
        }

        const allowedPermissions = ['run-scheduler', 'manage-infra', 'super-admin'];
        return permissions.value.some((perm) => allowedPermissions.includes(perm));
    });

    /**
     * Build HTTP headers with static key and/or bearer token.
     */
    function getHeaders(): HeadersInit {
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        };

        if (staticHeaderKey.value.trim() !== '') {
            headers[headerName.value] = staticHeaderKey.value.trim();
        }

        if (bearerToken.value.trim() !== '') {
            headers['Authorization'] = `Bearer ${bearerToken.value.trim()}`;
        }

        return headers;
    }

    /**
     * Internal request wrapper with performance timing and history logging.
     */
    async function executeRequest(endpoint: string, payload: Record<string, any> = {}): Promise<InfraExecutionResult> {
        if (!canAccessInfra.value) {
            const forbiddenResult: InfraExecutionResult = {
                success: false,
                message: 'Access Denied: You do not have permission to execute infrastructure commands.',
                exitCode: 403,
                output: 'Permission Denied',
                executedAt: new Date().toISOString(),
                durationMs: 0,
                endpoint,
                error: 'Forbidden',
            };
            lastResult.value = forbiddenResult;
            return forbiddenResult;
        }

        loading.value = true;
        const startTime = performance.now();
        const url = `${apiBaseUrl.value.replace(/\/$/, '')}${endpoint}`;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify(payload),
            });

            const durationMs = Math.round(performance.now() - startTime);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errorResult: InfraExecutionResult = {
                    success: false,
                    message: data.message ?? `HTTP Error ${response.status}: ${response.statusText}`,
                    exitCode: response.status,
                    output: data.message ?? response.statusText,
                    executedAt: new Date().toISOString(),
                    durationMs,
                    endpoint,
                    error: `HTTP ${response.status}`,
                    raw: data,
                };
                lastResult.value = errorResult;
                history.value.unshift(errorResult);
                return errorResult;
            }

            const executionData = data.data ?? {};
            const result: InfraExecutionResult = {
                success: Boolean(executionData.success ?? true),
                message: data.message ?? 'Operation executed successfully.',
                exitCode: Number(executionData.exit_code ?? 0),
                output: String(executionData.output ?? 'Command executed with no output.'),
                executedAt: String(executionData.executed_at ?? new Date().toISOString()),
                durationMs,
                endpoint,
                raw: data,
            };

            lastResult.value = result;
            history.value.unshift(result);
            return result;
        } catch (err: any) {
            const durationMs = Math.round(performance.now() - startTime);
            const networkError: InfraExecutionResult = {
                success: false,
                message: err?.message ?? 'Network connection failure.',
                exitCode: 1,
                output: err?.message ?? 'Network Error',
                executedAt: new Date().toISOString(),
                durationMs,
                endpoint,
                error: err?.message,
            };
            lastResult.value = networkError;
            history.value.unshift(networkError);
            return networkError;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Trigger Laravel scheduler (`schedule:run`).
     */
    async function triggerScheduler(webhooks?: WebhooksPayload): Promise<InfraExecutionResult> {
        return executeRequest('/api/infra/scheduler', { webhooks });
    }

    /**
     * Scan and sync subscription cycles across tenants.
     */
    async function runSubscriptionCheck(tenantId?: string, webhooks?: WebhooksPayload): Promise<InfraExecutionResult> {
        const parameters: Record<string, string> = {};
        if (tenantId && tenantId.trim() !== '') {
            parameters['--tenant'] = tenantId.trim();
        }

        return executeRequest('/api/infra/artisan', {
            command: 'subscriptions:check-cycles',
            parameters,
            webhooks,
        });
    }

    /**
     * Run an arbitrary Artisan command with parameters and optional webhooks.
     */
    async function runArtisan(
        command: string,
        parameters: Record<string, any> = {},
        webhooks?: WebhooksPayload
    ): Promise<InfraExecutionResult> {
        return executeRequest('/api/infra/artisan', {
            command,
            parameters,
            webhooks,
        });
    }

    /**
     * Run queue worker (process jobs).
     */
    async function runQueueWorker(
        options: {
            connection?: string;
            queue?: string;
            max_jobs?: number;
            stop_when_empty?: boolean;
            [key: string]: any;
        } = {},
        webhooks?: WebhooksPayload
    ): Promise<InfraExecutionResult> {
        return executeRequest('/api/infra/queue', {
            connection: options.connection ?? 'redis',
            queue: options.queue ?? 'default',
            max_jobs: options.max_jobs ?? 1,
            stop_when_empty: options.stop_when_empty ?? true,
            webhooks,
        });
    }

    function clearLastResult(): void {
        lastResult.value = null;
    }

    function clearHistory(): void {
        history.value = [];
    }

    return {
        // State
        apiBaseUrl,
        staticHeaderKey,
        headerName,
        bearerToken,
        userRole,
        permissions,
        loading,
        lastResult,
        history,
        canAccessInfra,

        // Actions
        triggerScheduler,
        runSubscriptionCheck,
        runArtisan,
        runQueueWorker,
        clearLastResult,
        clearHistory,
    };
}
