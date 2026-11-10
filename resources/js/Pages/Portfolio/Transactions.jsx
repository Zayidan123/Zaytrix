import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';

export default function Transactions() {
    const { data, setData, post, processing, errors } = useForm({
        symbol: '', type: 'buy', amount: '', price: '', fee: 0
    });

    const submit = (e) => {
        e.preventDefault();
        post('/api/portfolio/transaction', {
            onSuccess: () => { setData({ symbol: '', amount: '', price: '', fee: 0 }); },
        });
    };

    return (
        <div className="p-6 space-y-6">
            <Head title="Transaksi" />
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">Transaksi</h1>
                <Link href="/portfolio" className="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Kembali ke Portfolio</Link>
            </div>

            <form onSubmit={submit} className="bg-white p-6 rounded-lg border space-y-4 max-w-md">
                <div>
                    <label className="block text-sm font-medium mb-1">Symbol</label>
                    <input type="text" value={data.symbol} onChange={e => setData('symbol', e.target.value.toUpperCase())}
                        className="w-full p-2 border rounded" placeholder="BTC, ETH..." required />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Tipe</label>
                        <select value={data.type} onChange={e => setData('type', e.target.value)} className="w-full p-2 border rounded">
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Fee</label>
                        <input type="number" step="0.01" value={data.fee} onChange={e => setData('fee', e.target.value)}
                            className="w-full p-2 border rounded" placeholder="0" />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Amount</label>
                        <input type="number" step="0.00000001" value={data.amount} onChange={e => setData('amount', e.target.value)}
                            className="w-full p-2 border rounded" placeholder="0.1" required />
                    </div>
                    <div>
                        <label className="block text-sm font-medium mb-1">Price (USD)</label>
                        <input type="number" step="0.01" value={data.price} onChange={e => setData('price', e.target.value)}
                            className="w-full p-2 border rounded" placeholder="50000" required />
                    </div>
                </div>
                <button type="submit" disabled={processing} className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                    {processing ? 'Menyimpan...' : 'Simpan Transaksi'}
                </button>
                {errors.symbol && <p className="text-red-500 text-sm">{errors.symbol}</p>}
            </form>

            <div className="bg-white rounded-lg border">
                <h3 className="p-4 border-b font-medium">Riwayat Transaksi</h3>
                <TransactionsList />
            </div>
        </div>
    );
}

function TransactionsList() {
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/api/portfolio/history')
            .then(r => r.json())
            .then(data => { setTransactions(data); setLoading(false); });
    }, []);

    if (loading) return <div className="p-6 text-center">Loading...</div>;

    return (
        <table className="w-full">
            <thead className="bg-gray-50"><tr><th className="p-3 text-left">Symbol</th><th className="p-3 text-center">Type</th><th className="p-3 text-right">Amount</th><th className="p-3 text-right">Price</th><th className="p-3 text-right">Fee</th><th className="p-3 text-right">PnL</th><th className="p-3 text-left">Time</th></tr></thead>
            <tbody>
                {transactions.map((t, i) => (
                    <tr key={i} className="border-t">
                        <td className="p-3 font-medium">{t.symbol}</td>
                        <td className="p-3 text-center"><span className={`px-2 py-1 rounded text-xs ${t.type === 'buy' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{t.type}</span></td>
                        <td className="p-3 text-right">{parseFloat(t.amount).toLocaleString()}</td>
                        <td className="p-3 text-right">${parseFloat(t.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td className="p-3 text-right">${parseFloat(t.fee).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                        <td className={`p-3 text-right ${parseFloat(t.pnl) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                            ${parseFloat(t.pnl).toLocaleString(undefined, {minimumFractionDigits: 2})}
                        </td>
                        <td className="p-3 text-left text-sm text-gray-500">{new Date(t.executed_at).toLocaleString()}</td>
                    </tr>
                ))}
                {!transactions.length && <tr><td colSpan={7} className="p-6 text-center text-gray-500">Belum ada transaksi</td></tr>}
            </tbody>
        </table>
    );
}
