import { Head, Link, useForm } from '@inertiajs/react';

export default function Register() {
  const { data, setData, post, processing, errors } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
  return (
    <>
      <Head title="Register" />
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <form onSubmit={e => { e.preventDefault(); post('/register'); }} className="bg-white p-8 rounded-lg shadow-md w-full max-w-md space-y-4">
          <h1 className="text-2xl font-bold text-center">Register</h1>
          <input placeholder="Name" value={data.name} onChange={e=>setData('name',e.target.value)} className="w-full border rounded px-3 py-2" />
          <input type="email" placeholder="Email" value={data.email} onChange={e=>setData('email',e.target.value)} className="w-full border rounded px-3 py-2" />
          {errors.email && <p className="text-red-500 text-sm">{errors.email}</p>}
          <input type="password" placeholder="Password" value={data.password} onChange={e=>setData('password',e.target.value)} className="w-full border rounded px-3 py-2" />
          <input type="password" placeholder="Confirm Password" value={data.password_confirmation} onChange={e=>setData('password_confirmation',e.target.value)} className="w-full border rounded px-3 py-2" />
          <button disabled={processing} className="w-full bg-blue-600 text-white py-2 rounded">Register</button>
          <div className="text-center text-sm"><Link href="/login" className="text-blue-600">Login</Link></div>
        </form>
      </div>
    </>
  );
}
