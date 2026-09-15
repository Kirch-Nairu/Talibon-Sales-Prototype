import { useEffect, useRef } from 'react';

type PollTask = (signal: AbortSignal) => Promise<void>;

export function useVisiblePolling(
    task: PollTask,
    intervalMs: number,
    enabled = true,
) {
    const taskRef = useRef(task);
    const inFlight = useRef(false);

    useEffect(() => {
        taskRef.current = task;
    }, [task]);

    useEffect(() => {
        if (!enabled) return;

        let active = true;
        let controller: AbortController | null = null;

        const run = async () => {
            if (
                !active
                || inFlight.current
                || document.visibilityState !== 'visible'
            ) {
                return;
            }

            inFlight.current = true;
            controller = new AbortController();

            try {
                await taskRef.current(controller.signal);
            } catch (error) {
                if (!(error instanceof DOMException && error.name === 'AbortError')) {
                    // Background refresh is best-effort. The next visible poll retries.
                }
            } finally {
                inFlight.current = false;
                controller = null;
            }
        };

        const timer = window.setInterval(() => void run(), intervalMs);
        const onFocus = () => void run();
        const onVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                void run();
            }
        };

        window.addEventListener('focus', onFocus);
        document.addEventListener('visibilitychange', onVisibilityChange);

        return () => {
            active = false;
            controller?.abort();
            window.clearInterval(timer);
            window.removeEventListener('focus', onFocus);
            document.removeEventListener('visibilitychange', onVisibilityChange);
        };
    }, [enabled, intervalMs]);
}
