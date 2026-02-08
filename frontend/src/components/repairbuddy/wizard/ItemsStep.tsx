"use client";

import React from "react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";

type DeviceContextOption = {
  value: number;
  label: string;
};

type BaseServiceLine = {
  id: string;
  service: { name: string };
  device_id: number | null;
  qty: string;
  price: string;
};

type BasePartLine = {
  id: string;
  part: { name: string; code?: string | null; capacity?: string | null };
  device_id: number | null;
  qty: string;
  price: string;
};

type BaseOtherItemLine = {
  id: string;
  name: string;
  qty: string;
  price: string;
};

export function ItemsStep<
  TServiceLine extends BaseServiceLine,
  TPartLine extends BasePartLine,
  TOtherLine extends BaseOtherItemLine,
>({
  deviceContextOptions,
  disabled,

  services,
  setServices,
  parts,
  setParts,
  otherItems,
  setOtherItems,

  onAddService,
  serviceAddDisabled,
  serviceAddLabel,
  onCreateService,
  serviceCreateDisabled,
  serviceCreateLabel,

  onAddPart,
  partAddDisabled,
  partAddLabel,
  onCreatePart,
  partCreateDisabled,
  partCreateLabel,

  otherItemsTitle,
  otherItemsDescription,
  otherItemsAddLabel,
  createOtherItem,
  otherItemNamePlaceholder,
  otherItemPricePlaceholder,
}: {
  deviceContextOptions: DeviceContextOption[];
  disabled: boolean;

  services: TServiceLine[];
  setServices: React.Dispatch<React.SetStateAction<TServiceLine[]>>;
  parts: TPartLine[];
  setParts: React.Dispatch<React.SetStateAction<TPartLine[]>>;
  otherItems: TOtherLine[];
  setOtherItems: React.Dispatch<React.SetStateAction<TOtherLine[]>>;

  onAddService: () => void;
  serviceAddDisabled?: boolean;
  serviceAddLabel: string;
  onCreateService?: () => void;
  serviceCreateDisabled?: boolean;
  serviceCreateLabel?: string;

  onAddPart: () => void;
  partAddDisabled?: boolean;
  partAddLabel: string;
  onCreatePart?: () => void;
  partCreateDisabled?: boolean;
  partCreateLabel?: string;

  otherItemsTitle: string;
  otherItemsDescription: string;
  otherItemsAddLabel: string;
  createOtherItem: () => TOtherLine;
  otherItemNamePlaceholder: string;
  otherItemPricePlaceholder: string;
}) {
  return (
    <div className="space-y-6">
      <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-zinc-50/60 p-3">
        <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="text-sm font-semibold text-[var(--rb-text)]">Services</div>
              <div className="mt-1 text-xs text-zinc-600">Add services for each selected device and adjust quantities/prices.</div>
            </div>
            <div className="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={serviceAddDisabled ?? disabled}
                onClick={onAddService}
              >
                {serviceAddLabel}
              </Button>
              {typeof onCreateService === "function" && serviceCreateLabel ? (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  disabled={serviceCreateDisabled ?? disabled}
                  onClick={onCreateService}
                >
                  {serviceCreateLabel}
                </Button>
              ) : null}
            </div>
          </div>
        </div>

        {services.length > 0 ? (
          <div className="mt-3 space-y-2">
            {services.map((line) => {
              const deviceLabel =
                typeof line.device_id === "number"
                  ? deviceContextOptions.find((o) => o.value === line.device_id)?.label ?? `Device #${line.device_id}`
                  : "—";

              const qtyNum = Number(line.qty);
              const priceNum = Number(line.price);
              const total = (Number.isFinite(qtyNum) ? qtyNum : 0) * (Number.isFinite(priceNum) ? priceNum : 0);

              return (
                <div
                  key={line.id}
                  className="flex flex-col gap-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.service.name}</div>
                    <div className="mt-1 text-xs text-zinc-600">
                      {deviceLabel}
                      <span className="mx-2">•</span>
                      Qty: {line.qty || "0"}
                      <span className="mx-2">•</span>
                      Price: {line.price || "0"}
                      <span className="mx-2">•</span>
                      Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={disabled}
                      onClick={() => setServices((prev) => prev.filter((x) => x.id !== line.id))}
                    >
                      Remove
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="mt-3 text-sm text-zinc-600">No services added yet.</div>
        )}
      </div>

      <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-zinc-50/60 p-3">
        <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="text-sm font-semibold text-[var(--rb-text)]">Parts</div>
              <div className="mt-1 text-xs text-zinc-600">Add parts for each selected device and adjust quantities/prices.</div>
            </div>
            <div className="flex items-center gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={partAddDisabled ?? disabled}
                onClick={onAddPart}
              >
                {partAddLabel}
              </Button>
              {typeof onCreatePart === "function" && partCreateLabel ? (
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  disabled={partCreateDisabled ?? disabled}
                  onClick={onCreatePart}
                >
                  {partCreateLabel}
                </Button>
              ) : null}
            </div>
          </div>
        </div>

        {parts.length > 0 ? (
          <div className="mt-3 space-y-2">
            {parts.map((line) => {
              const deviceLabel =
                typeof line.device_id === "number"
                  ? deviceContextOptions.find((o) => o.value === line.device_id)?.label ?? `Device #${line.device_id}`
                  : "—";

              const qtyNum = Number(line.qty);
              const priceNum = Number(line.price);
              const total = (Number.isFinite(qtyNum) ? qtyNum : 0) * (Number.isFinite(priceNum) ? priceNum : 0);

              return (
                <div
                  key={line.id}
                  className="flex flex-col gap-2 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                  <div className="min-w-0">
                    <div className="truncate text-sm font-semibold text-[var(--rb-text)]">{line.part.name}</div>
                    <div className="mt-1 text-xs text-zinc-600">
                      {deviceLabel}
                      <span className="mx-2">•</span>
                      Qty: {line.qty || "0"}
                      <span className="mx-2">•</span>
                      Price: {line.price || "0"}
                      <span className="mx-2">•</span>
                      Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}
                    </div>
                    {line.part.code || line.part.capacity ? (
                      <div className="mt-1 text-xs text-zinc-500">
                        {line.part.code ? `Code: ${line.part.code}` : null}
                        {line.part.code && line.part.capacity ? <span className="mx-2">•</span> : null}
                        {line.part.capacity ? `Capacity: ${line.part.capacity}` : null}
                      </div>
                    ) : null}
                  </div>

                  <div className="flex items-center gap-2">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={disabled}
                      onClick={() => setParts((prev) => prev.filter((x) => x.id !== line.id))}
                    >
                      Remove
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="mt-3 text-sm text-zinc-600">No parts added yet.</div>
        )}
      </div>

      <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-zinc-50/60 p-3">
        <div className="rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3">
          <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div className="text-sm font-semibold text-[var(--rb-text)]">{otherItemsTitle}</div>
              <div className="mt-1 text-xs text-zinc-600">{otherItemsDescription}</div>
            </div>
            <div>
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={disabled}
                onClick={() => {
                  setOtherItems((prev) => [...prev, createOtherItem()]);
                }}
              >
                {otherItemsAddLabel}
              </Button>
            </div>
          </div>
        </div>

        {otherItems.length > 0 ? (
          <div className="mt-3 space-y-2">
            {otherItems.map((line) => {
              const qtyNum = Number(line.qty);
              const priceNum = Number(line.price);
              const total = (Number.isFinite(qtyNum) ? qtyNum : 0) * (Number.isFinite(priceNum) ? priceNum : 0);

              return (
                <div
                  key={line.id}
                  className="flex flex-col gap-3 rounded-[var(--rb-radius-md)] border border-[var(--rb-border)] bg-white px-4 py-3"
                >
                  <div className="grid grid-cols-1 gap-3 md:grid-cols-3 md:items-end">
                    <div>
                      <div className="mb-1 text-xs text-zinc-600">Name</div>
                      <Input
                        value={line.name}
                        onChange={(e) => {
                          const v = e.target.value;
                          setOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, name: v } : x)));
                        }}
                        disabled={disabled}
                        placeholder={otherItemNamePlaceholder}
                      />
                    </div>

                    <div>
                      <div className="mb-1 text-xs text-zinc-600">Qty</div>
                      <Input
                        value={line.qty}
                        onChange={(e) => {
                          const v = e.target.value;
                          setOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, qty: v } : x)));
                        }}
                        disabled={disabled}
                      />
                    </div>

                    <div>
                      <div className="mb-1 text-xs text-zinc-600">Price</div>
                      <Input
                        value={line.price}
                        onChange={(e) => {
                          const v = e.target.value;
                          setOtherItems((prev) => prev.map((x) => (x.id === line.id ? { ...x, price: v } : x)));
                        }}
                        disabled={disabled}
                        placeholder={otherItemPricePlaceholder}
                      />
                    </div>
                  </div>

                  <div className="flex items-center justify-between gap-2">
                    <div className="text-xs text-zinc-600">Total: {Number.isFinite(total) ? total.toFixed(2) : "0.00"}</div>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={disabled}
                      onClick={() => setOtherItems((prev) => prev.filter((x) => x.id !== line.id))}
                    >
                      Remove
                    </Button>
                  </div>
                </div>
              );
            })}
          </div>
        ) : (
          <div className="mt-3 text-sm text-zinc-600">No other items added yet.</div>
        )}
      </div>
    </div>
  );
}
