"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/lib/auth";

export default function Home() {
  const auth = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (auth.loading) return;
    if (auth.isAuthenticated) {
      router.replace(auth.isAdmin ? "/admin" : "/app");
    } else {
      router.replace("/v2");
    }
  }, [auth.loading, auth.isAuthenticated, auth.isAdmin, router]);

  return null;
}
