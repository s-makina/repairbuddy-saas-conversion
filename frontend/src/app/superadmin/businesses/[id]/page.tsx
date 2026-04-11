import SABusinessDetailContent from '@/components/superadmin/businesses/SABusinessDetailContent';
import { notFound } from 'next/navigation';

interface PageProps {
  params: Promise<{ id: string }>;
}

export default async function BusinessDetailPage({ params }: PageProps) {
  const { id } = await params;
  const businessId = parseInt(id, 10);
  if (isNaN(businessId) || businessId <= 0) {
    notFound();
  }
  return <SABusinessDetailContent businessId={businessId} />;
}
