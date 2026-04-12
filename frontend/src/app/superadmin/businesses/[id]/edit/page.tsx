import SAEditBusinessContent from '@/components/superadmin/businesses/SAEditBusinessContent';
import { notFound } from 'next/navigation';

interface PageProps {
  params: Promise<{ id: string }>;
}

export default async function EditBusinessPage({ params }: PageProps) {
  const { id } = await params;
  const businessId = parseInt(id, 10);

  if (isNaN(businessId) || businessId <= 0) {
    notFound();
  }

  return <SAEditBusinessContent businessId={businessId} />;
}
