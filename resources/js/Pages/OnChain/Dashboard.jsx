import { Head, Link } from '@inertiajs/react';

function money(n) {
  if (n == null) return '-';
  if (n >= 1e9) return (n/1e9).toFixed(2)+'B';
  if (n >= 1e6) return (n/1e6).toFixed(2)+'M';
  return Number(n).toLocaleString();
}

export default function OnChainDashboard({ onchain, whales }) {
  const price = onchain?.price || {};
  const diff = onchain?.difficulty || {};
  const fees = onchain?.fees || {};
  const blocks = onchain?.blocks || [];
  const pools = onchain?.pools || [];
  const hashrate = onchain?.hashrate || {};

  return (
    <>
      <Head title="On-Chain" />
      <div className="min-h-screen bg-gray-50 p-4 md:p-8">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl md:text-3xl font-bold">On-Chain</h1>
          <div className="flex gap-3 text-sm">
            <Link href="/dashboard" className="text-blue-600">Market</Link>
            <Link href="/onchain" className="text-blue-600">On-Chain</Link>
          </div>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-gray-500 text-sm">BTC Price</p>
            <p className="text-xl font-bold">${money(price?.USD)}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-gray-500 text-sm">Difficulty Change</p>
            <p className={`text-xl font-bold ${diff?.difficultyChange >= 0 ? 'text-green-600' : 'text-red-600'}`}>
              {diff?.difficultyChange?.toFixed(2)}%
            </p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-gray-500 text-sm">Fastest Fee (sats/vB)</p>
            <p className="text-xl font-bold">{fees?.fastestFee ?? '-'}</p>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <p className="text-gray-500 text-sm">Blocks in MemPool</p>
            <p className="text-xl font-bold">{onchain?.mempool_blocks?.length ?? 0}</p>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <div className="bg-white rounded-lg shadow p-4">
            <h2 className="font-semibold mb-3">Recent Blocks</h2>
            <div className="space-y-2 text-sm">
              {blocks.slice(0, 8).map((b, i) => (
                <div key={b?.height ?? i} className="flex justify-between border-b pb-1">
                  <span>#{b?.height}</span>
                  <span>{b?.tx_count ?? 0} tx</span>
                </div>
              ))}
              {blocks.length === 0 && <p className="text-gray-400">No data</p>}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <h2 className="font-semibold mb-3">Mining Pools (1w)</h2>
            <div className="space-y-2 text-sm">
              {(pools?.pools || []).slice(0, 8).map((p, i) => (
                <div key={p?.poolId ?? i} className="flex justify-between border-b pb-1">
                  <span>{p?.name}</span>
                  <span>{p?.blockCount}</span>
                </div>
              ))}
              {(!pools?.pools || pools.pools.length === 0) && <p className="text-gray-400">No data</p>}
            </div>
          </div>
          <div className="bg-white rounded-lg shadow p-4">
            <h2 className="font-semibold mb-3">Hashrate (3d)</h2>
            <div className="space-y-2 text-sm">
              {(hashrate?.hashrates || []).slice(0, 6).map((h, i) => (
                <div key={h?.timestamp ?? i} className="flex justify-between border-b pb-1">
                  <span>{new Date(h?.timestamp * 1000).toLocaleDateString()}</span>
                  <span>{money(h?.avgHashrate)}</span>
                </div>
              ))}
              {(!hashrate?.hashrates || hashrate.hashrates.length === 0) && <p className="text-gray-400">No data</p>}
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow p-4">
          <h2 className="font-semibold mb-3">Whale Radar (BTCUSDT)</h2>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-100 text-gray-600">
                <tr>
                  <th className="px-3 py-2 text-left">Time</th>
                  <th className="px-3 py-2 text-left">Side</th>
                  <th className="px-3 py-2 text-right">Price</th>
                  <th className="px-3 py-2 text-right">Qty</th>
                  <th className="px-3 py-2 text-right">USD</th>
                </tr>
              </thead>
              <tbody>
                {(whales?.whales || []).map((w, i) => (
                  <tr key={i} className="border-t">
                    <td className="px-3 py-2">{w?.t ? new Date(w.t).toLocaleTimeString() : '-'}</td>
                    <td className={`px-3 py-2 ${w?.side === 'BUY' ? 'text-green-600' : 'text-red-600'}`}>{w?.side}</td>
                    <td className="px-3 py-2 text-right">${Number(w?.price).toLocaleString()}</td>
                    <td className="px-3 py-2 text-right">{Number(w?.qty).toFixed(4)}</td>
                    <td className="px-3 py-2 text-right">${money(w?.usd)}</td>
                  </tr>
                ))}
                {(!whales?.whales || whales.whales.length === 0) && (
                  <tr><td colSpan={5} className="px-3 py-4 text-center text-gray-400">No whales captured</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </>
  );
}