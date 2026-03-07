import { Suspense } from "react";
import { SAPlanBuilderContent } from "@/components/superadmin/billing";

export default function PlanBuilderPage() {
  return (
    <Suspense>
      <SAPlanBuilderContent />
    </Suspense>
  );
}
