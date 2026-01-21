import { toast } from "sonner";

export type NotifyStatus = "success" | "error" | "info" | "warning" | "loading";

type NotifyOptions = {
  description?: string;
  id?: string | number;
  duration?: number;
};

export const notify = {
  success(message: string, options: NotifyOptions = {}) {
    return toast.success(message, options);
  },

  error(message: string, options: NotifyOptions = {}) {
    return toast.error(message, options);
  },

  info(message: string, options: NotifyOptions = {}) {
    return toast.info(message, options);
  },

  warning(message: string, options: NotifyOptions = {}) {
    return toast.warning(message, options);
  },

  loading(message: string, options: NotifyOptions = {}) {
    return toast.loading(message, options);
  },

  dismiss(id?: string | number) {
    toast.dismiss(id);
  },

  message(status: NotifyStatus, message: string, options: NotifyOptions = {}) {
    if (status === "success") return toast.success(message, options);
    if (status === "error") return toast.error(message, options);
    if (status === "warning") return toast.warning(message, options);
    if (status === "loading") return toast.loading(message, options);
    return toast.info(message, options);
  },
};
