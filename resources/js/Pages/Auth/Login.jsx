import { Head, Link, useForm } from '@inertiajs/react';

export default function Login() {
  const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false });
  return (
    <>
      <Head title="Login" />
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <form onSubmit={e => { e.preventDefault(); post('/login'); }} className="bg-white p-8 rounded-lg shadow-md w-full max-w-md space-y-4">
          <h1 className="text-2xl font-bold text-center">Zaytrix Login</h1>
          <input type="email" placeholder="Email" value={data.email} onChange={e=>setData('email',e.target.value)} className="w-full border rounded px-3 py-2" />
          {errors.email && <p className="text-red-500 text-sm">{errors.email}</p>}
          <input type="password" placeholder="Password" value={data.password} onChange={e=>setData('password',e.target.value)} className="w-full border rounded px-3 py-2" />
          <button disabled={processing} className="w-full bg-blue-600 text-white py-2 rounded">Login</button>
          <div className="text-center text-sm">
            <Link href="/register" className="text-blue-600">Register</Link>
            {' | '}
            <a href="/auth/google" className="text-blue-600">Google</a>
          </div>
        </form>
      </div>
    </>
  );
}
