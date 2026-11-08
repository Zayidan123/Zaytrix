import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function money(n) {
  if (n == null) return '-';
  if (n >= 1e9) return (n/1e9).toFixed(2)+'B';
  if (n >= 1e6) return (n/1e6).toFixed(2)+'M';
  return Number(n).toLocaleString();
}

export default function Dashboard({ coins, global }) {
  const [page, setPage] = useState(1);
  const perPage = 25;
  const totalPages = Math.ceil(coins.length / perPage);
  const slice = coins.slice((page-1)*perPage, page*perPage);

  return (
    <>
      <Head title="Market Dashboard" />
      <div className="min-h-screen bg-gray-50 p-4 md:p-8">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl md:text-3xl font-bold">Zaytrix Market</h1>
          <div className="flex gap-3 text-sm">
            <Link href="/dashboard" className="text-blue-600">Dashboard</Link>
            <button onClick={() => router.post('/logout')} className="text-red-600">Logout</button>
          </div>
        </div>

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
