import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorSetup({ secret, qr }) {
  const { data, setData, post, processing } = useForm({ code: '' });
  return (
    <>
      <Head title="2FA Setup" />
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="bg-white p-8 rounded-lg shadow-md w-full max-w-md space-y-4 text-center">
          <h1 className="text-2xl font-bold">Setup 2FA</h1>
          <p className="text-sm text-gray-600">Scan QR code dengan Google Authenticator</p>
          <img src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qr)}`} alt="QR" className="mx-auto" />
          <p className="font-mono text-xs break-all">{secret}</p>
          <form onSubmit={e => { e.preventDefault(); post('/2fa/verify'); }} className="space-y-2">
            <input placeholder="6-digit code" value={data.code} onChange={e=>setData('code',e.target.value)} className="w-full border rounded px-3 py-2 text-center" maxLength={6} />
            <button disabled={processing} className="w-full bg-blue-600 text-white py-2 rounded">Verify</button>
          </form>
        </div>
      </div>
    </>
  );
}
