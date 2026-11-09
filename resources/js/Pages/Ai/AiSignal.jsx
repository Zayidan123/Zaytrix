import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function AiSignal({ onchainSummary }) {
  const [form, setForm] = useState({ symbol: 'BTCUSDT', side: 'long', risk_pct: 1.0 });
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await fetch('/ai/signal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(form),
      });
      const data = await res.json();
      setResult(data);
    } catch (err) {
      setResult({ error: err.message });
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Head title="AI Signal" />
      <div className="min-h-screen bg-gray-50 p-8">
        <h1 className="text-3xl font-bold mb-2">AI Trading Signal</h1>
        <p className="text-gray-600 mb-6">Get entry, SL, TP and RRR from AI based on on-chain data.</p>

        <form onSubmit={submit} className="bg-white rounded-lg shadow p-6 max-w-2xl space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">Symbol</label>
            <input value={form.symbol} onChange={e => setForm({...form, symbol: e.target.value})}
              className="w-full border rounded px-3 py-2" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Side</label>
            <select value={form.side} onChange={e => setForm({...form, side: e.target.value})}
              className="w-full border rounded px-3 py-2">
              <option value="long">Long</option>
              <option value="short">Short</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Risk %</label>
            <input type="number" step="0.1" min="0.1" max="10" value={form.risk_pct}
              onChange={e => setForm({...form, risk_pct: parseFloat(e.target.value)})}
              className="w-full border rounded px-3 py-2" />
          </div>
          <button type="submit" disabled={loading}
            className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
            {loading ? 'Generating...' : 'Generate Signal'}
          </button>
        </form>

        {result && (
          <div className="mt-6 bg-white rounded-lg shadow p-6 max-w-2xl">
            <h2 className="text-xl font-bold mb-4">Signal Result</h2>
            {result.error ? (
              <p className="text-red-600">{result.error}</p>
            ) : (
              <pre className="bg-gray-100 p-4 rounded text-sm overflow-auto">
                {JSON.stringify(result, null, 2)}
              </pre>
            )}
          </div>
        )}
      </div>
    </>
  );
}
