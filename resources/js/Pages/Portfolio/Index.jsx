import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

export default function Portfolio() {
    const { props } = usePage();
    const [summary, setSummary] = useState(props.initialSummary || { total_value: 0, total_pnl: 0, positions: [] });
    const [loading, setLoading] = useState(false);

    const fetchSummary = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/portfolio/summary');
            const data = await res.json();
            setSummary(data);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchSummary(); }, []);

    return (
        <div className="p-6 space-y-6">
            <Head title="Portfolio" />
            <div className="flex justify-between items-center">
                <h1 className="text-2xl font-bold">Portfolio</h1>
                <Link href="/portfolio/transactions" className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Transaksi Baru</Link>
            </div>

            <div className="grid gap-4 md:grid-cols-3">
                <div className="p-4 bg-gray-50 rounded-lg border">
                    <p className="text-sm text-gray-500">Total Nilai</p>
                    <p className="text-2xl font-bold">${summary.total_value?.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) || '0.00'}</p>
                </div>
                <div className="p-4 bg-gray-50 rounded-lg border">
                    <p className="text-sm text-gray-500">Total Cost</p>
                    <p className="text-2xl font-bold">${summary.total_cost?.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) || '0.00'}</p>
                </div>
                <div className="p-4 bg-gray-50 rounded-lg border">
                    <p className="text-sm text-gray-500">PnL</p>
                    <p className={`text-2xl font-bold ${(summary.total_pnl || 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        ${summary.total_pnl?.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) || '0.00'}
                        <span className="text-xl ml-2">({(summary.pnl_percent || 0).toFixed(2)}%)</span>
                    </p>
                </div>
            </div>

            <div className="bg-white rounded-lg border overflow-hidden">
                <table className="w-full">
                    <thead className="bg-gray-50">
                        <tr><th className="p-3 text-left">Symbol</th><th className="p-3 text-right">Amount</th><th className="p-3 text-right">Avg Price</th><th className="p-3 text-right">Current</th><th className="p-3 text-right">Value</th><th className="p-3 text-right">PnL</th></tr>
                    </thead>
                    <tbody>
                        {summary.positions?.map((p, i) => (
                            <tr key={i} className="border-t">
                                <td className="p-3 font-medium">{p.symbol}</td>
                                <td className="p-3 text-right">{p.amount?.toLocaleString()}</td>
                                <td className="p-3 text-right">${p.avg_price?.toLocaleString(undefined, {minimumFractionDigits: 4})}</td>
                                <td className="p-3 text-right">${p.current_price?.toLocaleString(undefined, {minimumFractionDigits: 4})}</td>
                                <td className="p-3 text-right">${p.value?.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                                <td className={`p-3 text-right ${(p.pnl || 0) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                    ${p.pnl?.toLocaleString(undefined, {minimumFractionDigits: 2})}
                                </td>
                            </tr>
                        ))}
                        {(!summary.positions?.length) && <tr><td colSpan={6} className="p-6 text-center text-gray-500">Belum ada posisi</td></tr>}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
