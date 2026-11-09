import { Head, Link } from '@inertiajs/react';
import { useState, useRef, useEffect } from 'react';

export default function AiChat() {
  const [messages, setMessages] = useState([
    { role: 'assistant', content: 'Zaytrix AI is ready. Ask about markets, on-chain signals, or request a trading signal.' }
  ]);
  const [input, setInput] = useState('');
  const [streaming, setStreaming] = useState(false);
  const [error, setError] = useState(null);
  const scrollRef = useRef(null);

  useEffect(() => {
    scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' });
  }, [messages]);

  async function send() {
    const text = input.trim();
    if (!text || streaming) return;
    setError(null);
    setMessages(m => [...m, { role: 'user', content: text }]);
    setInput('');
    setStreaming(true);

    const assistantMsg = { role: 'assistant', content: '' };
    setMessages(m => [...m, assistantMsg]);

    try {
      const res = await fetch('/ai/chat/stream', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'text/event-stream' },
        body: JSON.stringify({ message: text }),
        credentials: 'same-origin'
      });
      if (!res.ok || !res.body) throw new Error('Stream failed');

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop();
        for (const line of lines) {
          if (!line.startsWith('data:')) continue;
          const payload = line.slice(5).trim();
          if (payload === '[DONE]') { setStreaming(false); break; }
          try {
            const ev = JSON.parse(payload);
            if (ev.token) {
              setMessages(m => {
                const next = [...m];
                const last = next[next.length - 1];
                next[next.length - 1] = { ...last, content: last.content + ev.token };
                return next;
              });
            }
          } catch (_) {}
        }
      }
    } catch (e) {
      setError(e.message);
      setMessages(m => {
        const next = [...m];
        next[next.length - 1] = { role: 'assistant', content: 'Error: unable to reach AI stream.' };
        return next;
      });
    } finally {
      setStreaming(false);
    }
  }

  return (
    <>
      <Head title="AI Chat" />
      <div className="min-h-screen bg-gray-50 p-4 md:p-8">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl md:text-3xl font-bold">AI Chat</h1>
          <div className="flex gap-3 text-sm">
            <Link href="/dashboard" className="text-blue-600">Market</Link>
            <Link href="/onchain" className="text-blue-600">On-Chain</Link>
            <Link href="/ai/signal" className="text-blue-600">Signal</Link>
            <Link href="/ai/compare" className="text-blue-600">Compare</Link>
          </div>
        </div>

        <div ref={scrollRef} className="bg-white rounded-lg shadow flex flex-col" style={{ height: '72vh' }}>
          <div className="flex-1 overflow-y-auto p-4 space-y-3">
            {messages.map((m, i) => (
              <div key={i} className={`flex ${m.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                <div className={`max-w-[80%] px-4 py-2 rounded-lg text-sm whitespace-pre-wrap ${
                  m.role === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800'
                }`}>
                  {m.content}
                  {streaming && i === messages.length - 1 && !m.content && (
                    <span className="inline-block w-2 h-4 bg-gray-400 animate-pulse ml-1" />
                  )}
                </div>
              </div>
            ))}
          </div>

          {error && <div className="px-4 py-2 bg-red-50 text-red-600 text-sm border-t">{error}</div>}

          <div className="border-t p-3 flex gap-2">
            <input
              value={input}
              onChange={e => setInput(e.target.value)}
              onKeyDown={e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); } }}
              placeholder="Type a message..."
              disabled={streaming}
              className="flex-1 border rounded px-3 py-2 text-sm disabled:opacity-50"
            />
            <button onClick={send} disabled={streaming || !input.trim()}
              className="px-4 py-2 bg-blue-600 text-white rounded text-sm disabled:opacity-50">
              Send
            </button>
          </div>
        </div>
      </div>
    </>
  );
}
