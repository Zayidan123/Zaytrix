import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function money(n) {
    if (n == null) return '-';
    if (n >= 1e9) return (n/1e9).toFixed(2)+'B';
    if (n >= 1e6) return (n/1e6).toFixed(2)+'M';
    return Number(n).toLocaleString();
}

function StatCard({ title, value, color, href }) {
    return (
        <Link href={href} className="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
            <p className="text-gray-500 text-sm">{title}</p>
            <p className={`text-xl font-bold text-${color}-600`}>{value}</p>
        </Link>
    );
}

export default function Dashboard({ coins, global, onchain, whales, portfolio, paperTrading }) {
    const [page, setPage] = useState(1);
    const perPage = 25;
    const totalPages = Math.ceil(coins.length / perPage);
    const slice = coins.slice((page-1)*perPage, page*perPage);

    return (
        <>
            <Head title="Zaytrix Dashboard" />
            <div className="min-h-screen bg-gray-50 p-4 md:p-8">
                <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <h1 className="text-2xl md:text-3xl font-bold">Zaytrix Market</h1>
                    <div className="flex flex-wrap gap-3 text-sm">
                        <Link href="/portfolio" className="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">Portfolio</Link>
                        <Link href="/paper-trading" className="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">Paper Trading</Link>
                        <Link href="/settings/alerts" className="px-3 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200">Alerts</Link>
                        <Link href="/onchain" className="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">On-Chain</Link>
                        <button onClick={() => router.post('/logout')} className="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">Logout</button>
                    </div>
                </div>

                {/* Portfolio Summary */}
                {portfolio && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <StatCard title="Portfolio Value" value={`$${money(portfolio.total_value)}`} color="blue" href="/portfolio" />
                        <StatCard title="Total PnL" value={`$${money(portfolio.total_pnl)}`} color={portfolio.total_pnl >= 0 ? 'green' : 'red'} href="/portfolio" />
                        <StatCard title="PnL %" value={`${portfolio.pnl_percent?.toFixed(2)}%`} color={portfolio.pnl_percent >= 0 ? 'green' : 'red'} href="/portfolio" />
                        <StatCard title="Positions" value={portfolio.positions?.length || 0} color="purple" href="/portfolio" />
                    </div>
                )}

                {/* Paper Trading Summary */}
                {paperTrading && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <StatCard title="Open Trades" value={paperTrading.open_trades} color="yellow" href="/paper-trading" />
                        <StatCard title="Closed Trades" value={paperTrading.closed_trades} color="gray" href="/paper-trading" />
                        <StatCard title="Paper PnL" value={`$${money(paperTrading.total_pnl)}`} color={paperTrading.total_pnl >= 0 ? 'green' : 'red'} href="/paper-trading" />
                        <StatCard title="Win Rate" value={`${paperTrading.win_rate?.toFixed(1)}%`} color="purple" href="/paper-trading" />
                    </div>
                )}

                {/* Market Overview */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div className="bg-white rounded-lg shadow p-4">
                        <p className="text-gray-500 text-sm">Total Market Cap</p>
                        <p className="text-xl font-bold">${money(global?.total_market_cap?.usd)}</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-4">
                        <p className="text-gray-500 text-sm">24h Volume</p>
                        <p className="text-xl font-bold">${money(global?.total_volume?.usd)}</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-4">
                        <p className="text-gray-500 text-sm">BTC Dominance</p>
                        <p className="text-xl font-bold">{global?.market_cap_percentage?.btc?.toFixed(2)}%</p>
                    </div>
                    <div className="bg-white rounded-lg shadow p-4">
                        <p className="text-gray-500 text-sm">Active Coins</p>
                        <p className="text-xl font-bold">{global?.active_cryptocurrencies?.toLocaleString()}</p>
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100 text-gray-600">
                                <tr>
                                    <th className="px-3 py-2 text-left">#</th>
                                    <th className="px-3 py-2 text-left">Coin</th>
                                    <th className="px-3 py-2 text-right">Price</th>
                                    <th className="px-3 py-2 text-right">24h %</th>
                                    <th className="px-3 py-2 text-right">Market Cap</th>
                                </tr>
                            </thead>
                            <tbody>
                                {slice.map((c, i) => (
                                    <tr key={c.id} className="border-t hover:bg-gray-50">
                                        <td className="px-3 py-2">{(page-1)*perPage + i + 1}</td>
                                        <td className="px-3 py-2">
                                            <div className="flex items-center gap-2">
                                                <img src={c.image} alt={c.symbol} className="w-6 h-6 rounded-full" />
                                                <div>
                                                    <p className="font-medium">{c.name}</p>
                                                    <p className="text-gray-400 uppercase text-xs">{c.symbol}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-3 py-2 text-right">${Number(c.current_price).toLocaleString()}</td>
                                        <td className={`px-3 py-2 text-right ${c.price_change_percentage_24h >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                            {c.price_change_percentage_24h?.toFixed(2)}%
                                        </td>
                                        <td className="px-3 py-2 text-right">${money(c.market_cap)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex items-center justify-between px-4 py-3 border-t">
                        <button onClick={() => setPage(p => Math.max(1, p-1))} disabled={page === 1}
                            className="px-3 py-1 rounded border disabled:opacity-40">Prev</button>
                        <span className="text-sm text-gray-600">Page {page} / {totalPages}</span>
                        <button onClick={() => setPage(p => Math.min(totalPages, p+1))} disabled={page === totalPages}
                            className="px-3 py-1 rounded border disabled:opacity-40">Next</button>
                    </div>
                </div>
            </div>
        </>
    );
}
