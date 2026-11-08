import { Head } from '@inertiajs/react';

export default function Dashboard() {
  return (
    <>
      <Head title="Dashboard" />
      <div className="min-h-screen bg-gray-50 p-8">
        <h1 className="text-3xl font-bold">Zaytrix Dashboard</h1>
        <p className="text-gray-600 mt-2">Welcome to your crypto dashboard.</p>
      </div>
    </>
  );
}
