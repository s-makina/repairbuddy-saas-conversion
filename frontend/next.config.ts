import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async redirects() {
    return [
      {
        source: "/login",
        destination: "/v2/login",
        permanent: false,
      },
      {
        source: "/register",
        destination: "/v2/register",
        permanent: false,
      },
    ];
  },
};

export default nextConfig;
