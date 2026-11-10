import React, { useEffect, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Settings({ user, api_health }) {
    const [activeTab, setActiveTab] = useState('profile');
    const [users, setUsers] = useState([]);
    const [loadingUsers, setLoadingUsers] = useState(false);

    const { data, setData, patch, processing } = useForm({
        name: user.name,
        email: user.email,
        current_password: '',
        password: '',
        password_confirmation: '',
        theme: user.theme,
    });

    useEffect(() => {
        setData('theme', user.theme);
        if (user.is_admin) fetchUsers();
    }, [user.is_admin]);

    const fetchUsers = async () => {
        setLoadingUsers(true);
        try {
            const res = await fetch('/api/settings/users');
            const data = await res.json();
            setUsers(data);
        } catch (e) { console.error(e); }
        finally { setLoadingUsers(false); }
    };

    const submitProfile = (e) => {
        e.preventDefault();
        patch('/api/settings/profile', { onSuccess: () => {} });
    };

    const submitPassword = (e) => {
        e.preventDefault();
        patch('/api/settings/password', {
            onSuccess: () => setData({ current_password: '', password: '', password_confirmation: '' }),
        });
    };

    const submitTheme = () => {
        patch('/api/settings/theme', { theme: data.theme });
    };

    const createUser = async (e) => {
        e.preventDefault();
        try {
            const res = await fetch('/api/settings/users', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: 'User Baru', email: `user${Date.now()}@test.com`, password: 'password123', is_admin: false }),
            });
            if (res.ok) fetchUsers();
        } catch (e) { console.error(e); }
    };

    const deleteUser = async (id) => {
        if (!confirm('Hapus user ini?')) return;
        try {
            await fetch(`/api/settings/users/${id}`, { method: 'DELETE' });
            fetchUsers();
        } catch (e) { console.error(e); }
    };

    const refreshHealth = async () => {
        const res = await fetch('/api/settings/api-health');
        const data = await res.json();
        // Force re-render by updating state
        setApiHealth(data);
    };

    const [apiHealth, setApiHealth] = useState(api_health);

    return (
        <div className="p-6 space-y-6">
            <Head title="Settings" />
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">Settings</h1>
                <Link href="/dashboard" className="text-sm text-blue-600 hover:underline">← Dashboard</Link>
            </div>

            <div className="border-b">
                <nav className="flex gap-4">
                    <TabButton active={activeTab === 'profile'} onClick={() => setActiveTab('profile')}>Profil</TabButton>
                    <TabButton active={activeTab === 'theme'} onClick={() => setActiveTab('theme')}>Tema</TabButton>
                    <TabButton active={activeTab === 'api'} onClick={() => setActiveTab('api')}>API Health</TabButton>
                    {user.is_admin && <TabButton active={activeTab === 'users'} onClick={() => setActiveTab('users')}>Kelola User (Admin)</TabButton>}
                </nav>
            </div>

            {activeTab === 'profile' && (
                <div className="max-w-2xl space-y-6">
                    <form onSubmit={submitProfile} className="bg-white p-6 rounded-lg border space-y-4">
                        <h3 className="font-medium">Profil</h3>
                        <div>
                            <label className="block text-sm font-medium mb-1">Nama</label>
                            <input value={data.name} onChange={e => setData('name', e.target.value)} className="w-full p-2 border rounded" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Email</label>
                            <input type="email" value={data.email} onChange={e => setData('email', e.target.value)} className="w-full p-2 border rounded" />
                        </div>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded">Simpan Profil</button>
                    </form>

                    <form onSubmit={submitPassword} className="bg-white p-6 rounded-lg border space-y-4">
                        <h3 className="font-medium">Ubah Password</h3>
                        <div>
                            <label className="block text-sm font-medium mb-1">Password Saat Ini</label>
                            <input type="password" value={data.current_password} onChange={e => setData('current_password', e.target.value)} className="w-full p-2 border rounded" required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Password Baru</label>
                            <input type="password" value={data.password} onChange={e => setData('password', e.target.value)} className="w-full p-2 border rounded" required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Konfirmasi Password</label>
                            <input type="password" value={data.password_confirmation} onChange={e => setData('password_confirmation', e.target.value)} className="w-full p-2 border rounded" required />
                        </div>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-blue-600 text-white rounded">Ubah Password</button>
                    </form>
                </div>
            )}

            {activeTab === 'theme' && (
                <div className="max-w-md">
                    <div className="bg-white p-6 rounded-lg border space-y-4">
                        <h3 className="font-medium">Tema</h3>
                        <div className="grid grid-cols-2 gap-4">
                            <button onClick={() => { setData('theme', 'light'); submitTheme(); }} className={`p-4 border-2 rounded ${data.theme === 'light' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'}`}>
                                <div className="flex items-center justify-center gap-2">
                                    <span className="text-2xl">☀️</span>
                                    <span className="font-medium">Light</span>
                                </div>
                            </button>
                            <button onClick={() => { setData('theme', 'dark'); submitTheme(); }} className={`p-4 border-2 rounded ${data.theme === 'dark' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'}`}>
                                <div className="flex items-center justify-center gap-2">
                                    <span className="text-2xl">🌙</span>
                                    <span className="font-medium">Dark</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {activeTab === 'api' && (
                <div className="max-w-2xl">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="font-medium">Koneksi API</h3>
                        <button onClick={refreshHealth} className="px-3 py-1 text-sm border rounded hover:bg-gray-50">Refresh</button>
                    </div>
                    <div className="space-y-3">
                        {Object.entries(apiHealth).map(([key, val]) => (
                            <div key={key} className="bg-white p-4 rounded-lg border flex justify-between items-center">
                                <div>
                                    <p className="font-medium capitalize">{key}</p>
                                    <p className="text-sm text-gray-500">{val.error || `Latency: ${val.latency_ms}ms`}</p>
                                </div>
                                <span className={`px-3 py-1 rounded-full text-sm ${val.status === 'healthy' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                    {val.status === 'healthy' ? '✓ Healthy' : '✗ Error'}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {activeTab === 'users' && user.is_admin && (
                <div className="max-w-3xl">
                    <div className="bg-white p-6 rounded-lg border mb-6">
                        <h3 className="font-medium mb-4">Tambah User Baru (Max 5)</h3>
                        <form onSubmit={createUser} className="flex gap-2">
                            <button type="submit" className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Tambah User Demo</button>
                        </form>
                    </div>

                    <div className="bg-white rounded-lg border">
                        {loadingUsers ? (
                            <div className="p-8 text-center">Loading...</div>
                        ) : (
                            <table className="w-full">
                                <thead className="bg-gray-50"><tr><th className="p-3 text-left">Nama</th><th className="p-3 text-left">Email</th><th className="p-3 text-center">Admin</th><th className="p-3 text-left">Dibuat</th><th className="p-3 text-right">Aksi</th></tr></thead>
                                <tbody>
                                    {users.map((u, i) => (
                                        <tr key={i} className="border-t">
                                            <td className="p-3">{u.name}</td>
                                            <td className="p-3">{u.email}</td>
                                            <td className="p-3 text-center"><span className={`px-2 py-1 rounded text-xs ${u.is_admin ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'}`}>{u.is_admin ? 'Ya' : 'Tidak'}</span></td>
                                            <td className="p-3 text-sm text-gray-500">{new Date(u.created_at).toLocaleDateString()}</td>
                                            <td className="p-3 text-right">
                                                {u.id !== user.id && (
                                                    <button onClick={() => deleteUser(u.id)} className="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {!users.length && <tr><td colSpan={5} className="p-6 text-center text-gray-500">Tidak ada user</td></tr>}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

function TabButton({ active, onClick, children }) {
    return (
        <button onClick={onClick} className={`pb-2 border-b-2 font-medium ${active ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'}`}>
            {children}
        </button>
    );
}
