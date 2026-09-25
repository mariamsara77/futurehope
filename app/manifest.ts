import type { MetadataRoute } from "next";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন",
    short_name: "ফিউচার হোপ",
    description: "মানুষের পাশে থেকে একটি সুন্দর, মানবিক ও সম্ভাবনাময় ভবিষ্যৎ গড়ার উদ্যোগ।",
    id: "/",
    start_url: "/",
    scope: "/",
    display: "standalone",
    display_override: ["window-controls-overlay", "standalone"],
    orientation: "portrait-primary",
    background_color: "#fafafa",
    theme_color: "#059669",
    lang: "bn-BD",
    dir: "ltr",
    categories: ["social", "lifestyle", "education"],
    icons: [
      {
        src: "/android-chrome-192x192.png",
        sizes: "192x192",
        type: "image/png",
        purpose: "any",
      },
      {
        src: "/android-chrome-512x512.png",
        sizes: "512x512",
        type: "image/png",
        purpose: "any maskable",
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
        icons: [{ src: "/android-chrome-192x192.png", sizes: "192x192", type: "image/png" }],
      },
      {
        name: "কার্যক্রম দেখুন",
        short_name: "কার্যক্রম",
        url: "/activities",
        icons: [{ src: "/android-chrome-192x192.png", sizes: "192x192", type: "image/png" }],
      },
      {
        name: "সদস্য দেখুন",
        short_name: "সদস্য",
        url: "/members",
        icons: [{ src: "/android-chrome-192x192.png", sizes: "192x192", type: "image/png" }],
      },
    ],
  };
}
