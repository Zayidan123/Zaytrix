import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function AiCompare({ documents }) {
  const [selected, setSelected] = useState([]);
  const [question, setQuestion] = useState('');
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const toggleDoc = (id) => {
    setSelected(prev =>
      prev.includes(id) ? prev.filter(d => d !== id) : [...prev, id]
    );
  };

  const submit = async (e) => {
    e.preventDefault();
    if (selected.length < 2) return;
    setLoading(true);
    try {
      const res = await fetch('/ai/compare', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ doc_ids: selected, question }),
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
      <Head title="AI Compare" />
      <div className="min-h-screen bg-gray-50 p-8">
        <h1 className="text-3xl font-bold mb-2">AI Document Compare</h1>
        <p className="text-gray-600 mb-6">Upload 2+ PDFs and ask AI to compare them.</p>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div className="lg:col-span-1 bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-bold mb-4">Select Documents</h2>
            {documents && documents.length > 0 ? (
              <div className="space-y-2">
                {documents.map(doc => (
                  <label key={doc.id} className="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" checked={selected.includes(doc.id)}
                      onChange={() => toggleDoc(doc.id)} />
                    <span className="text-sm truncate">{doc.title}</span>
                  </label>
                ))}
              </div>
            ) : (
              <p className="text-gray-500 text-sm">No documents uploaded yet.</p>
            )}
          </div>

          <div className="lg:col-span-2 space-y-6">
            <form onSubmit={submit} className="bg-white rounded-lg shadow p-6">
              <label className="block text-sm font-medium mb-2">Question</label>
              <textarea value={question} onChange={e => setQuestion(e.target.value)}
                rows={3} className="w-full border rounded px-3 py-2"
                placeholder="Ask AI to compare the selected documents..." />
              <button type="submit" disabled={loading || selected.length < 2}
                className="mt-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
                {loading ? 'Comparing...' : 'Compare'}
              </button>
            </form>

            {result && (
              <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-xl font-bold mb-4">Comparison Result</h2>
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
        </div>
      </div>
    </>
  );
}
