import type { Metadata } from "next";
import { Hind_Siliguri } from "next/font/google";
import "./globals.css";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import AuthProvider from "@/components/AuthProvider";
import VisitorTracker from "@/components/VisitorTracker";
import PWARegister from "@/components/PWARegister";

const hindSiliguri = Hind_Siliguri({
  weight: ["300", "400", "500", "600", "700"],
  subsets: ["bengali"],
  display: "swap",
});

export const metadata: Metadata = {
  metadataBase: new URL("https://futurehope.totthobox.com"),
  title: {
    default: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন",
    template: "%s | ফিউচার হোপ",
  },
  description:
    "মানুষের পাশে থেকে একটি সুন্দর, মানবিক ও সম্ভাবনাময় ভবিষ্যৎ গড়ার উদ্যোগ।",
  robots: { index: true, follow: true },
  applicationName: "ফিউচার হোপ",
  appleWebApp: {
    capable: true,
    title: "ফিউচার হোপ",
    statusBarStyle: "default",
  },
  icons: {
    icon: [
      { url: "/favicon-16x16.png", sizes: "16x16", type: "image/png" },
      { url: "/favicon-32x32.png", sizes: "32x32", type: "image/png" },
      { url: "/favicon.ico" },
    ],
    apple: [{ url: "/apple-touch-icon.png", sizes: "180x180", type: "image/png" }],
  },
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="bn" className="scroll-smooth">
      <body
        className={`${hindSiliguri.className} min-h-screen bg-zinc-50 text-zinc-900 antialiased`}
      >
        <AuthProvider>
          <VisitorTracker />
          <PWARegister />
          <Navbar />
          <main className="min-h-[calc(100vh-8rem)]">{children}</main>
          <Footer />
        </AuthProvider>
      </body>
    </html>
  );
}
