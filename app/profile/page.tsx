"use client";

import { ChangeEvent, FormEvent, useEffect, useState } from "react";
import { ApiError, deleteAvatar, getProfile, updateProfile, type Profile } from "@/lib/api";
import { useAuth } from "@/components/AuthProvider";

const initial={phone:"",father_name:"",mother_name:"",present_address:"",permanent_address:"",education:"",blood_group:"",bio:""};

export default function ProfilePage() {
  const { user, loading: authLoading } = useAuth();
  const [profile,setProfile]=useState<Profile|null>(null);
  const [form,setForm]=useState(initial);
  const [image,setImage]=useState<File|null>(null);
  const [preview,setPreview]=useState("");
  const [loading,setLoading]=useState(true);
  const [saving,setSaving]=useState(false);
  const [message,setMessage]=useState("");
  const [error,setError]=useState("");

  useEffect(()=>{ if(!user){setLoading(false);return;} getProfile().then(p=>{setProfile(p);setForm({phone:p.phone||"",father_name:p.father_name||"",mother_name:p.mother_name||"",present_address:p.present_address||"",permanent_address:p.permanent_address||"",education:p.education||"",blood_group:p.blood_group||"",bio:p.bio||""});setPreview(p.avatar_url||"");}).catch(e=>setError(e instanceof ApiError?e.message:"Profile তথ্য আনা যায়নি।")).finally(()=>setLoading(false)); },[user]);

  function chooseImage(e:ChangeEvent<HTMLInputElement>){const file=e.target.files?.[0];if(!file)return;if(file.size>2*1024*1024){setError("ছবির আকার সর্বোচ্চ ২ MB হতে হবে।");return;}setImage(file);setPreview(URL.createObjectURL(file));}
  async function submit(e:FormEvent){e.preventDefault();setSaving(true);setMessage("");setError("");try{const p=await updateProfile(form,image);setProfile(p);setPreview(p.avatar_url||"");setImage(null);setMessage("Profile সফলভাবে সংরক্ষণ হয়েছে।");}catch(e){setError(e instanceof ApiError?e.message:"Profile সংরক্ষণ করা যায়নি।");}finally{setSaving(false);}}
  async function removeAvatar(){setError("");try{const p=await deleteAvatar();setProfile(p);setPreview("");setMessage("Profile ছবি সরানো হয়েছে।");}catch(e){setError(e instanceof ApiError?e.message:"ছবি সরানো যায়নি।");}}

  if(authLoading||loading) return <main className="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8"><div className="h-72 animate-pulse rounded-3xl bg-zinc-200"/></main>;
  if(!user) return <main className="mx-auto max-w-3xl px-4 py-24 text-center sm:px-6"><div className="rounded-3xl bg-white p-10 shadow-sm ring-1 ring-zinc-200"><h1 className="text-3xl font-bold">Profile দেখতে লগইন করুন</h1><p className="mt-3 leading-7 text-zinc-500">লগইন করলে আপনার ব্যক্তিগত তথ্য, যোগাযোগ ও profile photo এখান থেকে পরিচালনা করতে পারবেন।</p></div></main>;

  return <main className="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <div className="rounded-3xl bg-zinc-950 p-7 text-white sm:p-10"><p className="text-sm font-semibold text-emerald-300">সদস্য profile</p><h1 className="mt-2 text-3xl font-bold sm:text-4xl">আপনার তথ্য ও পরিচিতি</h1><p className="mt-3 max-w-3xl leading-7 text-zinc-300">আপনার সংগঠনের profile তথ্য হালনাগাদ রাখুন। প্রয়োজন অনুযায়ী ভবিষ্যতে এই তথ্য member directory ও দায়িত্ব ব্যবস্থাপনায় ব্যবহার করা যাবে।</p></div>
    <form onSubmit={submit} className="mt-7 space-y-7">
      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8"><div className="flex flex-col gap-7 sm:flex-row sm:items-center"><div className="flex h-28 w-28 shrink-0 overflow-hidden rounded-full bg-emerald-50 ring-4 ring-emerald-50">{preview?<img src={preview} alt="Profile preview" className="h-full w-full object-cover"/>:<div className="m-auto text-3xl font-bold text-emerald-700">{(user.name||user.email).charAt(0).toUpperCase()}</div>}</div><div><h2 className="text-xl font-bold">Profile photo</h2><p className="mt-1 text-sm text-zinc-500">JPG, PNG বা WebP • সর্বোচ্চ ২ MB</p><div className="mt-4 flex flex-wrap gap-2"><label className="cursor-pointer rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">ছবি নির্বাচন<input type="file" accept="image/jpeg,image/png,image/webp" onChange={chooseImage} className="hidden"/></label>{profile?.avatar_url&&<button type="button" onClick={removeAvatar} className="rounded-xl border border-zinc-200 px-4 py-2.5 text-sm font-semibold text-zinc-600 hover:bg-zinc-50">ছবি সরান</button>}</div></div></div></section>
      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8"><h2 className="text-2xl font-bold">মৌলিক তথ্য</h2><div className="mt-6 grid gap-5 md:grid-cols-2"><ReadOnly label="নাম" value={user.name||"—"}/><ReadOnly label="ইমেইল" value={user.email}/><Field label="মোবাইল নম্বর" name="phone" value={form.phone} onChange={setForm}/><Field label="শিক্ষাগত যোগ্যতা" name="education" value={form.education} onChange={setForm}/><Field label="পিতার নাম" name="father_name" value={form.father_name} onChange={setForm}/><Field label="মাতার নাম" name="mother_name" value={form.mother_name} onChange={setForm}/><label className="block"><span className="mb-2 block text-sm font-semibold">রক্তের গ্রুপ</span><select value={form.blood_group} onChange={e=>setForm(f=>({...f,blood_group:e.target.value}))} className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-emerald-500"><option value="">নির্বাচন করুন</option>{["A+","A-","B+","B-","O+","O-","AB+","AB-"].map(x=><option key={x}>{x}</option>)}</select></label></div></section>
      <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-zinc-200 sm:p-8"><h2 className="text-2xl font-bold">ঠিকানা ও পরিচিতি</h2><div className="mt-6 grid gap-5 md:grid-cols-2"><Textarea label="বর্তমান ঠিকানা" name="present_address" value={form.present_address} onChange={setForm}/><Textarea label="স্থায়ী ঠিকানা" name="permanent_address" value={form.permanent_address} onChange={setForm}/><div className="md:col-span-2"><Textarea label="নিজের সম্পর্কে সংক্ষেপে" name="bio" value={form.bio} onChange={setForm} rows={5}/></div></div></section>
      {error&&<div role="alert" className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-medium text-red-700">{error}</div>}{message&&<div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-700">{message}</div>}
      <div className="flex justify-end"><button disabled={saving} className="rounded-2xl bg-emerald-600 px-7 py-3.5 font-bold text-white hover:bg-emerald-700 disabled:opacity-60">{saving?"সংরক্ষণ হচ্ছে...":"Profile সংরক্ষণ করুন"}</button></div>
    </form>
  </main>;
}
function ReadOnly({label,value}:{label:string;value:string}){return <div><span className="mb-2 block text-sm font-semibold">{label}</span><div className="rounded-2xl border border-zinc-200 bg-zinc-100 px-4 py-3 text-zinc-600">{value}</div></div>}
function Field({label,name,value,onChange}:{label:string;name:keyof typeof initial;value:string;onChange:React.Dispatch<React.SetStateAction<typeof initial>>}){return <label className="block"><span className="mb-2 block text-sm font-semibold">{label}</span><input value={value} onChange={e=>onChange(f=>({...f,[name]:e.target.value}))} className="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 outline-none focus:border-emerald-500 focus:bg-white"/></label>}
function Textarea({label,name,value,onChange,rows=4}:{label:string;name:keyof typeof initial;value:string;onChange:React.Dispatch<React.SetStateAction<typeof initial>>;rows?:number}){return <label className="block"><span className="mb-2 block text-sm font-semibold">{label}</span><textarea rows={rows} value={value} onChange={e=>onChange(f=>({...f,[name]:e.target.value}))} className="w-full resize-y rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 leading-7 outline-none focus:border-emerald-500 focus:bg-white"/></label>}
