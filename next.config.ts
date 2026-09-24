import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "futurehope.totthobox.com",
      },
    ],
  },
};

export default nextConfig;
