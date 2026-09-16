import type { NextConfig } from 'next';

const apiUpstream = process.env.API_UPSTREAM ?? 'http://web:80';

const nextConfig: NextConfig = {
  async rewrites() {
    return {
      beforeFiles: [
        {
          source: '/api/:path*',
          destination: `${apiUpstream}/api/:path*`,
        },
      ],
    };
  },
};

export default nextConfig;
