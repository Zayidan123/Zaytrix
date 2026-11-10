import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';

export default function PaperTrading() {
    const { data, setData, post, processing, errors } = useForm({
        symbol: '', side: 'long', entry_price: '', sl_price: '', tp_price: '', amount: ''
    });
    const [summary, setSummary] = useState({ open_trades: 0, closed_trades: 0, total_pnl: 0, win_rate: 0, trades: [] });

    const fetchSummary = async () => {
        try {
            const res = await fetch('/api/paper/summary');
            const data = await res.json();
            setSummary(data);
        } catch (e) { console.error(e); }
    };

    useEffect(() => { fetchSummary(); }, []);

    const submit = (e) => {
        e.preventDefault();
        post('/api/paper/trade', {
            onSuccess: () => { setData({ symbol: '', entry_price: '', sl_price: '', tp_price: '', amount: '' }); fetchSummary(); },
        });
    };

    return (
        <div className="p-6 space-y-6">
            <Head title="Paper Trading" />
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">Paper Trading</h1>
            </div>

            <div className="grid gap-4 md:grid-cols-4">
                <StatCard title="Open" value={summary.open_trades} color="blue" />
                <StatCard title="Closed" value={summary.closed_trades} color="gray" />
                <StatCard title="Total PnL" value={`$${summary.total_pnl?.toLocaleString(undefined, {minimumFractionDigits: 2})}`} color={summary.total_pnl >= 0 ? 'green' : 'red'} />
                <StatCard title="Win Rate" value={`${summary.win_rate?.toFixed(1)}%`} color="purple" />
            </div>

            <form onSubmit={submit} className="bg-white p-6 rounded-lg border space-y-4 max-w-2xl">
                <h3 className="font-medium text-lg">Buka Posisi Baru</h3>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Symbol</label>
                        <input type="text" value={data.symbol} onChange={e => setData('symbol', e.target.value.toUpperCase())}
                            className="w-full p-2 border rounded" placeholder="BTC" required />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Side</label>
                        <select value={data.side} onChange={e => setData('side', e.target.value)} className="w-full p-2 border rounded">
                            <option value="long">Long</option>
                            <option value="short">Short</option>
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Entry Price</label>
                        <input type="number" step="0.01" value={data.entry_price} onChange={e => setData('entry_price', e.target.value)}
                            className="w-full p-2 border rounded" required />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Stop Loss (opsional)</label>
                        <input type="number" step="0.01" value={data.sl_price} onChange={e => setData('sl_price', e.target.value)}
                            className="w-full p-2 border rounded" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Take Profit (opsional)</label>
                        <input type="number" step="0.01" value={data.tp_price} onChange={e => setData('tp_price', e.target.value)}
                            className="w-full p-2 border rounded" />
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Amount</label>
                    <input type="number" step="0.00000001" value={data.amount} onChange={e => setData('amount', e.target.value)}
                        className="w-full p-2 border rounded" required />
                </div>
                <button type="submit" disabled={processing} className="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                    {processing ? 'Membuka...' : 'Buka Posisi'}
                </button>
                {errors.symbol && <p className="text-red-500 text-sm">{errors.symbol}</p>}
            </form>

            <div className="bg-white rounded-lg border">
                <h3 className="p-4 border-b font-medium">Daftar Posisi</h3>
                <table className="w-full">
                    <thead className="bg-gray-50"><tr><th className="p-3 text-left">Symbol</th><th className="p-3 text-center">Side</th><th className="p-3 text-right">Entry</th><th className="p-3 text-right">SL</th><th className="p-3 text-right">TP</th><th className="p-3 text-right">Amount</th><th className="p-3 text-center">Status</th><th className="p-3 text-right">PnL</th><th className="p-3 text-right">RRR</th></tr></thead>
                    <tbody>
                        {summary.trades?.map((t, i) => (
                            <tr key={i} className="border-t">
                                <td className="p-3 font-medium">{t.symbol}</td>
                                <td className="p-3 text-center"><span className={`px-2 py-1 rounded text-xs ${t.side === 'long' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{t.side}</span></td>
                                <td className="p-3 text-right">${parseFloat(t.entry_price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td className="p-3 text-right">{t.sl_price ? `$${parseFloat(t.sl_price).toLocaleString(undefined, {minimumFractionDigits: 2})}` : '-'}</td>
                                <td className="p-3 text-right">{t.tp_price ? `$${parseFloat(t.tp_price).toLocaleString(undefined, {minimumFractionDigits: 2})}` : '-'}</td>
                                <td className="p-3 text-right">{parseFloat(t.amount).toLocaleString()}</td>
                                <td className="p-3 text-center"><span className={`px-2 py-1 rounded text-xs ${t.status === 'open' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'}`}>{t.status}</span></td>
                                <td className={`p-3 text-right ${t.pnl ? (parseFloat(t.pnl) >= 0 ? 'text-green-600' : 'text-red-600') : ''}`}>
                                    {t.pnl ? `$${parseFloat(t.pnl).toLocaleString(undefined, {minimumFractionDigits: 2})}` : '-'}
                                </td>
                                <td className="p-3 text-right">{t.rrr_ratio ? parseFloat(t.rrr_ratio).toFixed(2) : '-'}</td>
                            </tr>
                        ))}
                        {!summary.trades?.length && <tr><td colSpan={9} className="p-6 text-center text-gray-500">Belum ada posisi</td></tr>}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function StatCard({ title, value, color }) {
    return (
        <div className="p-4 bg-gray-50 rounded-lg border">
            <p className="text-sm text-gray-500">{title}</p>
            <p className={`text-2xl font-bold text-${color}-600`}>{value}</p>
        </div>
    );
}
