import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function News() {
    const [news, setNews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('all');
    const [refreshing, setRefreshing] = useState(false);

    const fetchNews = async () => {
        setLoading(true);
        try {
            const res = await fetch('/api/news');
            const data = await res.json();
            setNews(data);
        } catch (e) { console.error(e); }
        finally { setLoading(false); }
    };

    const refreshNews = async () => {
        setRefreshing(true);
        try {
            await fetch('/api/news/refresh', { method: 'POST' });
            await fetchNews();
        } catch (e) { console.error(e); }
        finally { setRefreshing(false); }
    };

    useEffect(() => { fetchNews(); }, []);

    const filteredNews = news.filter(n => filter === 'all' || (filter === 'high' && n.high_impact));

    return (
        <div className="p-6 space-y-6">
            <Head title="News High Impact" />
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <h1 className="text-2xl font-bold">Crypto News</h1>
                <div className="flex gap-2">
                    <select value={filter} onChange={e => setFilter(e.target.value)} className="p-2 border rounded">
                        <option value="all">Semua</option>
                        <option value="high">High Impact Only</option>
                    </select>
                    <button onClick={refreshNews} disabled={refreshing} className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                        {refreshing ? 'Refresh...' : 'Refresh'}
                    </button>
                </div>
            </div>

            {loading ? (
                <div className="p-8 text-center">Loading...</div>
            ) : (
                <div className="space-y-4">
                    {filteredNews.map((item, i) => (
                        <NewsCard key={i} item={item} />
                    ))}
                    {!filteredNews.length && <p className="text-center text-gray-500 py-8">Tidak ada berita</p>}
                </div>
            )}
        </div>
    );
}

function NewsCard({ item }) {
    const timeAgo = new Date(item.published_at).toLocaleString();
    const keywords = item.impact_keywords?.join(', ') || '';
    return (
        <Link href={item.url} target="_blank" rel="noopener noreferrer" className="block">
            <div className={`bg-white rounded-lg border p-4 hover:shadow-md transition-shadow ${item.high_impact ? 'border-red-300 bg-red-50' : ''}`}>
                <div className="flex items-start justify-between gap-4">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap mb-2">
                            <span className="px-2 py-1 text-xs bg-gray-100 rounded">{item.source}</span>
                            {item.high_impact && (
                                <span className="px-2 py-1 text-xs bg-red-100 text-red-700 rounded font-medium">⚡ HIGH IMPACT</span>
                            )}
                        </div>
                        <h3 className="font-medium text-lg mb-1">{item.title}</h3>
                        {item.description && (
                            <p className="text-gray-600 text-sm line-clamp-2">{item.description}</p>
                        )}
                        {keywords && (
                            <p className="text-xs text-gray-500 mt-2">Keywords: {keywords}</p>
                        )}
                        <p className="text-xs text-gray-400 mt-2">{timeAgo}</p>
                    </div>
                </div>
            </div>
        </Link>
    );
}
