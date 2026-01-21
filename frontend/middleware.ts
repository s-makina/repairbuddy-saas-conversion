import type { NextRequest } from "next/server";
import { NextResponse } from "next/server";

export function middleware(req: NextRequest) {
  const pathname = req.nextUrl.pathname;

  if (pathname === "/admin/tenants" || pathname.startsWith("/admin/tenants/")) {
    return NextResponse.json({ message: "Not found." }, { status: 404 });
  }

  if (pathname === "/admin/billing/tenants" || pathname.startsWith("/admin/billing/tenants/")) {
    return NextResponse.json({ message: "Not found." }, { status: 404 });
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/admin/:path*"],
};
