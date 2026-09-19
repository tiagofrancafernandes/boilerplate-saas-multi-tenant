const fs = require('fs');
const path = require('path');

/**
 * Determine the appropriate start script for a Nuxt 3 application:
 * - If `.output/server/index.mjs` exists, run the high-performance Nitro production build.
 * - Otherwise, fall back to running Nuxt development mode (`npm run dev`).
 *
 * @param {string} appDir
 * @returns {{ script: string, args: string }}
 */
function resolveNuxtScript(appDir) {
    const builtOutput = path.resolve(__dirname, 'apps', appDir, '.output', 'server', 'index.mjs');

    if (fs.existsSync(builtOutput)) {
        return {
            script: './.output/server/index.mjs',
            args: '',
        };
    }

    return {
        script: 'npm',
        args: 'run dev',
    };
}

module.exports = {
    apps: [
        // =========================================================================
        // 1. Frontend: Public Landing & Marketing Site (Nuxt 3) - Port 3000
        // =========================================================================
        {
            name: 'saas-site',
            cwd: './apps/site',
            ...resolveNuxtScript('site'),
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '350M',
            env: {
                PORT: 3000,
                HOST: '0.0.0.0',
                NODE_ENV: 'production',
            },
            env_development: {
                PORT: 3000,
                HOST: '0.0.0.0',
                NODE_ENV: 'development',
            },
        },

        // =========================================================================
        // 2. Frontend: Tenant App / Customer Dashboard (Nuxt 3) - Port 3001
        // =========================================================================
        {
            name: 'saas-web',
            cwd: './apps/web',
            ...resolveNuxtScript('web'),
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '350M',
            env: {
                PORT: 3001,
                HOST: '0.0.0.0',
                NODE_ENV: 'production',
            },
            env_development: {
                PORT: 3001,
                HOST: '0.0.0.0',
                NODE_ENV: 'development',
            },
        },

        // =========================================================================
        // 3. Frontend: Super Admin Portal (Nuxt 3) - Port 3002
        // =========================================================================
        {
            name: 'saas-admin',
            cwd: './apps/admin',
            ...resolveNuxtScript('admin'),
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '350M',
            env: {
                PORT: 3002,
                HOST: '0.0.0.0',
                NODE_ENV: 'production',
            },
            env_development: {
                PORT: 3002,
                HOST: '0.0.0.0',
                NODE_ENV: 'development',
            },
        },

        // =========================================================================
        // 4. Backend: Laravel API Server - Port 8000
        // =========================================================================
        {
            name: 'saas-api',
            cwd: './apps/api',
            script: 'artisan',
            interpreter: 'php',
            args: 'serve --host=0.0.0.0 --port=8000',
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '256M',
            env: {
                APP_ENV: 'local',
            },
        },

        // =========================================================================
        // 5. Backend: Laravel Queue Worker
        // =========================================================================
        {
            name: 'saas-worker',
            cwd: './apps/api',
            script: 'artisan',
            interpreter: 'php',
            args: 'queue:work --sleep=3 --tries=3 --max-time=3600',
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '256M',
            env: {
                APP_ENV: 'local',
            },
        },

        // =========================================================================
        // 6. Backend: Laravel Task Scheduler Worker
        // =========================================================================
        {
            name: 'saas-scheduler',
            cwd: './apps/api',
            script: 'artisan',
            interpreter: 'php',
            args: 'schedule:work',
            instances: 1,
            exec_mode: 'fork',
            autorestart: true,
            max_memory_restart: '128M',
            env: {
                APP_ENV: 'local',
            },
        },
    ],
};
