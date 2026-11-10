import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Head } from '@inertiajs/react';

export default function AlertsSettings() {
    const { data, setData, post, delete: destroy, processing, errors } = useForm({
        symbol: '', condition: 'above', target_price: ''
    });
    const [alerts, setAlerts] = useState([]);
    const [notifications, setNotifications] = useState([]);
    const [activeTab, setActiveTab] = useState('alerts');

    const fetchAlerts = async () => {
        try {
            const [a, n] = await Promise.all([
                fetch('/api/alerts').then(r => r.json()),
                fetch('/api/alerts/notifications').then(r => r.json())
            ]);
            setAlerts(a);
            setNotifications(n);
        } catch (e) { console.error(e); }
    };

    useEffect(() => { fetchAlerts(); }, []);

    const submit = (e) => {
        e.preventDefault();
        post('/api/alerts', {
            onSuccess: () => { setData({ symbol: '', target_price: '' }); fetchAlerts(); },
        });
    };

    const remove = (id) => {
        if (confirm('Hapus alert ini?')) {
            destroy(`/api/alerts/${id}`, { onSuccess: fetchAlerts });
        }
    };

    const markRead = (id) => {
        fetch(`/api/alerts/notifications/${id}/read`, { method: 'PATCH' })
            .then(() => fetchAlerts());
    };

    return (
        <div className="p-6 space-y-6">
            <Head title="Alert Settings" />
            <h1 className="text-2xl font-bold">Alert Settings</h1>

            <div className="border-b">
                <nav className="flex gap-4">
                    <button onClick={() => setActiveTab('alerts')} className={`pb-2 border-b-2 ${activeTab === 'alerts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'}`}>Price Alerts</button>
                    <button onClick={() => setActiveTab('notifications')} className={`pb-2 border-b-2 ${activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'}`}>Notifikasi ({notifications.filter(n => !n.read).length})</button>
                </nav>
            </div>

            {activeTab === 'alerts' && (
                <div className="grid gap-6 md:grid-cols-2">
                    <form onSubmit={submit} className="bg-white p-6 rounded-lg border space-y-4">
                        <h3 className="font-medium">Buat Alert Baru</h3>
                        <div>
                            <label className="block text-sm font-medium mb-1">Symbol</label>
                            <input type="text" value={data.symbol} onChange={e => setData('symbol', e.target.value.toUpperCase())}
                                className="w-full p-2 border rounded" placeholder="BTC" required />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">Kondisi</label>
                                <select value={data.condition} onChange={e => setData('condition', e.target.value)} className="w-full p-2 border rounded">
                                    <option value="above">Di atas (Above)</option>
                                    <option value="below">Di bawah (Below)</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Target Price</label>
                                <input type="number" step="0.01" value={data.target_price} onChange={e => setData('target_price', e.target.value)}
                                    className="w-full p-2 border rounded" placeholder="50000" required />
                            </div>
                        </div>
                        <button type="submit" disabled={processing} className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            {processing ? 'Menyimpan...' : 'Simpan Alert'}
                        </button>
                        {errors.symbol && <p className="text-red-500 text-sm">{errors.symbol}</p>}
                    </form>

                    <div className="bg-white rounded-lg border">
                        <h3 className="p-4 border-b font-medium">Alert Aktif</h3>
                        {alerts.length === 0 ? (
                            <p className="p-6 text-center text-gray-500">Belum ada alert</p>
                        ) : (
                            <ul className="divide-y">
                                {alerts.map((a, i) => (
                                    <li key={i} className="p-4 flex justify-between items-center">
                                        <div>
                                            <p className="font-medium">{a.symbol}</p>
                                            <p className="text-sm text-gray-500">
                                                {a.condition === 'above' ? 'Di atas' : 'Di bawah'} $${parseFloat(a.target_price).toLocaleString()}
                                            </p>
                                        </div>
                                        <button onClick={() => remove(a.id)} className="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            )}

            {activeTab === 'notifications' && (
                <div className="bg-white rounded-lg border">
                    {notifications.length === 0 ? (
                        <p className="p-6 text-center text-gray-500">Belum ada notifikasi</p>
                    ) : (
                        <ul className="divide-y">
                            {notifications.map((n, i) => (
                                <li key={i} className={`p-4 ${!n.read ? 'bg-blue-50' : ''}`}>
                                    <div className="flex justify-between">
                                        <div>
                                            <p className="font-medium">{n.title}</p>
                                            <p className="text-sm text-gray-500">{n.body}</p>
                                            <p className="text-xs text-gray-400 mt-1">{new Date(n.created_at).toLocaleString()}</p>
                                        </div>
                                        {!n.read && (
                                            <button onClick={() => markRead(n.id)} className="text-sm text-blue-600 hover:text-blue-800">Tandai dibaca</button>
                                        )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}
