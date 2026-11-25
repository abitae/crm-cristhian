import { router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import type { FlashToast } from '@/types/ui';

let toasterMounted = false;

function ensureToaster(): void {
    if (toasterMounted || typeof document === 'undefined') {
        return;
    }

    const host = document.createElement('div');
    host.setAttribute('data-app-toaster', '');
    document.body.appendChild(host);
    createApp({
        render: () =>
            h(Toaster, {
                position: 'bottom-right',
                richColors: true,
                closeButton: true,
            }),
    }).mount(host);
    toasterMounted = true;
}

export function notify(
    type: FlashToast['type'],
    message: string,
): void {
    ensureToaster();
    toast[type](message);
}

export function notifySuccess(message: string): void {
    notify('success', message);
}

export function notifyError(message: string): void {
    notify('error', message);
}

export function notifyInfo(message: string): void {
    notify('info', message);
}

export function notifyWarning(message: string): void {
    notify('warning', message);
}

export function initializeFlashToast(): void {
    ensureToaster();

    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data?.message || !data?.type) {
            return;
        }

        notify(data.type, data.message);
    });
}
