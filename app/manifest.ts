import type { MetadataRoute } from "next";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন",
    short_name: "ফিউচার হোপ",
    description:
      "মানুষের পাশে থেকে একটি সুন্দর, মানবিক ও সম্ভাবনাময় ভবিষ্যৎ গড়ার উদ্যোগ।",
    id: "/",
    start_url: "/",
    scope: "/",
    display: "standalone",
    display_override: ["standalone"],
    orientation: "portrait-primary",
    background_color: "#ffffff",
    theme_color: "#059669",
    lang: "bn-BD",
    dir: "ltr",
    categories: ["social", "lifestyle", "education"],
    icons: [
      {
        src: "/android-chrome-192x192.png",
        sizes: "192x192",
        type: "image/png",
        purpose: "any maskable",
      },
      {
        src: "/android-chrome-512x512.png",
        sizes: "512x512",
        type: "image/png",
        purpose: "any maskable",
      },
    ],
    screenshots: [
      {
        src: "/pwa-screenshot-mobile.svg",
        sizes: "540x960",
        type: "image/svg+xml",
        form_factor: "narrow",
        label: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন — মোবাইল",
      },
      {
        src: "/pwa-screenshot-wide.svg",
        sizes: "1280x720",
        type: "image/svg+xml",
        form_factor: "wide",
        label: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন — ওয়েব",
      },
    ],
    share_target: {
      action: "/share",
      method: "GET",
      enctype: "application/x-www-form-urlencoded",
      params: { title: "title", text: "text", url: "url" },
    },
    shortcuts: [
      {
        name: "কাজগুলো দেখুন",
        short_name: "কাজ",
        url: "/works",
        icons: [
          {
            src: "/android-chrome-192x192.png",
            sizes: "192x192",
            type: "image/png",
          },
        ],
      },
      {
        name: "কার্যক্রম দেখুন",
        short_name: "কার্যক্রম",
        url: "/activities",
        icons: [
          {
            src: "/android-chrome-192x192.png",
            sizes: "192x192",
            type: "image/png",
          },
        ],
      },
      {
        name: "সদস্য দেখুন",
        short_name: "সদস্য",
        url: "/members",
        icons: [
          {
            src: "/android-chrome-192x192.png",
            sizes: "192x192",
            type: "image/png",
          },
        ],
      },
    ],
  };
}
