/**
 * Toast notification area — renders auto-dismissing toasts.
 *
 * @package
 */

/**
 * WordPress dependencies
 */
import { SnackbarList } from "@wordpress/components";

/**
 * Internal dependencies
 */
import type { Toast } from "../hooks/useToast";

interface ToastAreaProps {
  toasts: Toast[];
  onRemove: (id: string) => void;
}

/**
 * Renders a stack of toast notifications using @wordpress/components SnackbarList.
 *
 * @param props          Component props.
 * @param props.toasts   Array of toast notifications.
 * @param props.onRemove Callback to dismiss a toast.
 * @return   The rendered toast list or null.
 */
export function ToastArea({ toasts, onRemove }: ToastAreaProps) {
  if (toasts.length === 0) {
    return null;
  }

  const notices = toasts.map((toast) => ({
    id: toast.id,
    content: toast.message,
    spokenMessage: toast.message,
    actions: [],
    isDismissible: true,
    onDismiss: () => onRemove(toast.id),
  }));

  return (
    <div
      style={{
        position: "fixed",
        bottom: "24px",
        right: "24px",
        zIndex: 100000,
      }}
    >
      <SnackbarList notices={notices} onRemove={onRemove} />
    </div>
  );
}
