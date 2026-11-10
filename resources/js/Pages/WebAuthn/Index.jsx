import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function WebAuthn({ credentials = [] }) {
    const [registering, setRegistering] = useState(false);
    const [loading, setLoading] = useState(false);
    const [name, setName] = useState('');
    const [error, setError] = useState('');

    const { post, processing } = useForm({});

    const startRegistration = async () => {
        setError('');
        setRegistering(true);
        try {
            const res = await fetch('/api/webauthn/register/options');
            const options = await res.json();
            if (!res.ok) throw new Error(options.error || 'Failed to get options');
            const credential = await navigator.credentials.create({ publicKey: toPublicKeyCredentialCreationOptions(options) });
            const response = {
                credential_id: base64UrlEncode(credential.rawId),
                public_key: base64UrlEncode(credential.response.getPublicKey()),
                attestation: {
                    client_data_json: base64UrlEncode(credential.response.clientDataJSON),
                    attestation_object: base64UrlEncode(credential.response.attestationObject),
                    transports: credential.response.transports || [],
                },
            };
            await post('/api/webauthn/register', { ...response, name });
            setName('');
            setRegistering(false);
            window.location.reload();
        } catch (e) {
            setError(e.message);
            setRegistering(false);
        }
    };

    const startLogin = async () => {
        setError('');
        setLoading(true);
        try {
            const res = await fetch('/api/webauthn/login/options');
            const options = await res.json();
            const credential = await navigator.credentials.get({ publicKey: toPublicKeyCredentialRequestOptions(options) });
            const response = {
                credential_id: base64UrlEncode(credential.rawId),
                signature: base64UrlEncode(credential.response.signature),
                client_data: base64UrlEncode(credential.response.clientDataJSON),
                auth_data: base64UrlEncode(credential.response.authenticatorData),
            };
            const authRes = await fetch('/api/webauthn/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(response),
            });
            const data = await authRes.json();
            if (data.token) {
                localStorage.setItem('webauthn_token', data.token);
                window.location.href = '/dashboard';
            }
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    };

    const deleteCredential = async (id) => {
        if (!confirm('Hapus security key ini?')) return;
        await fetch(`/api/webauthn/credentials/${id}`, { method: 'DELETE' });
        window.location.reload();
    };

    return (
        <div className="p-6 space-y-6 max-w-2xl">
            <Head title="WebAuthn / Security Keys" />
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">Security Keys (WebAuthn)</h1>
                <button onClick={() => window.location.href = '/settings'} className="text-sm text-blue-600 hover:underline">← Settings</button>
            </div>

            {error && <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded">{error}</div>}

            <div className="bg-white p-6 rounded-lg border space-y-4">
                <h3 className="font-medium">Register New Security Key</h3>
                <p className="text-sm text-gray-500">Gunakan sidik jari, Face ID, atau hardware key (YubiKey). Butuh HTTPS untuk production.</p>
                <input
                    type="text"
                    placeholder="Nama key (contoh: iPhone Face ID)"
                    value={name}
                    onChange={e => setName(e.target.value)}
                    className="w-full p-2 border rounded mb-2"
                />
                <button
                    onClick={startRegistration}
                    disabled={registering || processing || !navigator.credentials}
                    className="px-4 py-2 bg-green-600 text-white rounded disabled:opacity-50"
                >
                    {registering ? 'Mendaftarkan...' : 'Daftarkan Security Key'}
                </button>
                {!navigator.credentials && <p className="text-sm text-red-500">WebAuthn tidak didukung browser ini</p>}
            </div>

            <div className="bg-white p-6 rounded-lg border space-y-4">
                <h3 className="font-medium">Login dengan Security Key</h3>
                <button
                    onClick={startLogin}
                    disabled={loading || !navigator.credentials}
                    className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
                >
                    {loading ? 'Memverifikasi...' : 'Login dengan Security Key'}
                </button>
            </div>

            <div className="bg-white p-6 rounded-lg border">
                <h3 className="font-medium mb-4">Registered Keys</h3>
                {credentials.length === 0 ? (
                    <p className="text-gray-500">Belum ada security key terdaftar</p>
                ) : (
                    <ul className="space-y-2">
                        {credentials.map((c, i) => (
                            <li key={i} className="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <div>
                                    <p className="font-medium">{c.name || 'Security Key'}</p>
                                    <p className="text-sm text-gray-500">Last used: {c.last_used_at ? new Date(c.last_used_at).toLocaleString() : 'Never'}</p>
                                </div>
                                <button onClick={() => deleteCredential(c.id)} className="text-red-500 text-sm">Hapus</button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}

function toPublicKeyCredentialCreationOptions(options) {
    return {
        ...options,
        challenge: base64UrlDecode(options.challenge),
        user: { ...options.user, id: base64UrlDecode(options.user.id) },
        excludeCredentials: options.excludeCredentials?.map(c => ({ ...c, id: base64UrlDecode(c.id) })),
    };
}

function toPublicKeyCredentialRequestOptions(options) {
    return {
        ...options,
        challenge: base64UrlDecode(options.challenge),
        allowCredentials: options.allowCredentials?.map(c => ({ ...c, id: base64UrlDecode(c.id) })),
    };
}

function base64UrlEncode(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}
function base64UrlDecode(str) {
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    while (str.length % 4) str += '=';
    return Uint8Array.from(atob(str), c => c.charCodeAt(0));
}
