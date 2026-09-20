import type { Metadata } from "next";
import { Hind_Siliguri } from "next/font/google";
import "./globals.css";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import AuthProvider from "@/components/AuthProvider";

const hindSiliguri = Hind_Siliguri({ weight: ["300","400","500","600","700"], subsets: ["bengali"], display: "swap" });

export const metadata: Metadata = {
  metadataBase: new URL("https://futurehope.totthobox.com"),
  title: { default: "ফিউচার হোপ অ্যান্ড হিউম্যানিটি ফাউন্ডেশন", template: "%s | ফিউচার হোপ" },
  description: "মানুষের পাশে থেকে একটি সুন্দর, মানবিক ও সম্ভাবনাময় ভবিষ্যৎ গড়ার উদ্যোগ।",
  robots: { index: true, follow: true },
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return <html lang="bn" className="scroll-smooth"><body className={`${hindSiliguri.className} min-h-screen bg-zinc-50 text-zinc-900 antialiased`}><AuthProvider><Navbar /><main className="min-h-[calc(100vh-8rem)]">{children}</main><Footer /></AuthProvider></body></html>;
}
