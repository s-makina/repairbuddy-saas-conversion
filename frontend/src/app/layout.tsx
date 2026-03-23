import type { Metadata } from "next";
import "./globals.css";
import "@/app/(public)/v2.css";
import { Providers } from "@/providers";

export const metadata: Metadata = {
  title: "RepairBuddy",
  description: "RepairBuddy SaaS",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <head>
        <link
          rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.css"
        />
      </head>
      <body className="antialiased">
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
