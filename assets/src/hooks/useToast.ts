/**
 * Toast notification hook — auto-dismiss after a configurable delay.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { useState, useCallback, useRef } from "@wordpress/element";

export interface Toast {
  id: string;
  message: string;
  type: "success" | "error" | "info";
}

/**
 * Hook for toast notifications with auto-dismiss.
 *
 * @param dismissAfterMs Time before auto-dismiss (default 4000).
 * @return Hook state with toasts array, addToast, and removeToast.
 */
export function useToast(dismissAfterMs = 4000) {
  const [toasts, setToasts] = useState<Toast[]>([]);
  const counterRef = useRef(0);

  const addToast = useCallback(
    (message: string, type: Toast["type"] = "info") => {
      const id = `toast-${++counterRef.current}`;
      const toast: Toast = { id, message, type };

      setToasts((prev) => [...prev, toast]);

      setTimeout(() => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
      }, dismissAfterMs);
    },
    [dismissAfterMs],
  );

  const removeToast = useCallback((id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  return { toasts, addToast, removeToast };
}
