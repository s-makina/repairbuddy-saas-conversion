/** @type {import('next').NextConfig} */
const nextConfig = {
  async redirects() {
    return [
      {
        source: '/admin',
        destination: '/superadmin',
        permanent: true,
      },
      {
        source: '/admin/:path*',
        destination: '/superadmin/:path*',
        permanent: true,
      },
    ]
  },
};

module.exports = nextConfig;
