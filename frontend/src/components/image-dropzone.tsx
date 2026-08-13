"use client";

import { useCallback, useId, useRef, useState } from "react";
import { Upload, ImageIcon } from "lucide-react";
import { toast } from "sonner";
import { cn } from "@/lib/utils";

const DEFAULT_MAX_SIZE = 2 * 1024 * 1024; // 2MB
const DEFAULT_ACCEPT = [
  "image/jpeg",
  "image/jpg",
  "image/png",
  "image/gif",
  "image/svg+xml",
];

export interface ImageDropzoneProps {
  onFileSelect: (file: File) => void;
  accept?: string[];
  maxSize?: number;
  disabled?: boolean;
  className?: string;
  label?: string;
  hint?: string;
  hasPreview?: boolean;
}

export function ImageDropzone({
  onFileSelect,
  accept = DEFAULT_ACCEPT,
  maxSize = DEFAULT_MAX_SIZE,
  disabled = false,
  className,
  label,
  hint = "Formatos: JPG, PNG, GIF, SVG · Máximo: 2MB",
  hasPreview = false,
}: ImageDropzoneProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const inputId = useId();
  const [isDragging, setIsDragging] = useState(false);
  const dragDepth = useRef(0);

  const validateAndSelect = useCallback(
    (file: File | undefined | null) => {
      if (!file) return;

      if (file.size > maxSize) {
        toast.error(`Imagem muito grande! Tamanho máximo: ${Math.round(maxSize / (1024 * 1024))}MB`);
        return;
      }

      if (accept.length > 0 && !accept.includes(file.type)) {
        toast.error("Tipo de arquivo inválido! Use: JPG, PNG, GIF ou SVG");
        return;
      }

      onFileSelect(file);
    },
    [accept, maxSize, onFileSelect]
  );

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    validateAndSelect(file);
    // permite selecionar o mesmo arquivo de novo
    e.target.value = "";
  };

  const handleDragEnter = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (disabled) return;
    dragDepth.current += 1;
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (disabled) return;
    dragDepth.current -= 1;
    if (dragDepth.current <= 0) {
      dragDepth.current = 0;
      setIsDragging(false);
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    dragDepth.current = 0;
    setIsDragging(false);
    if (disabled) return;

    const file = e.dataTransfer.files?.[0];
    validateAndSelect(file);
  };

  const openFilePicker = () => {
    if (disabled) return;
    inputRef.current?.click();
  };

  return (
    <div className={cn("space-y-2", className)}>
      {label ? (
        <p className="text-sm font-medium leading-none">
          {label}
        </p>
      ) : null}

      <div
        role="button"
        tabIndex={disabled ? -1 : 0}
        aria-disabled={disabled}
        aria-label="Área para enviar imagem do produto"
        onClick={openFilePicker}
        onKeyDown={(e) => {
          if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            openFilePicker();
          }
        }}
        onDragEnter={handleDragEnter}
        onDragLeave={handleDragLeave}
        onDragOver={handleDragOver}
        onDrop={handleDrop}
        className={cn(
          "relative flex min-h-[140px] cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-6 text-center transition-colors outline-none",
          "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]",
          disabled && "pointer-events-none opacity-50",
          isDragging
            ? "border-primary bg-primary/5"
            : "border-muted-foreground/25 hover:border-primary/50 hover:bg-muted/40"
        )}
      >
        <input
          ref={inputRef}
          id={inputId}
          type="file"
          accept={accept.join(",")}
          className="sr-only"
          disabled={disabled}
          onChange={handleInputChange}
        />

        <div
          className={cn(
            "flex h-10 w-10 items-center justify-center rounded-full",
            isDragging ? "bg-primary/15 text-primary" : "bg-muted text-muted-foreground"
          )}
        >
          {isDragging ? <Upload className="h-5 w-5" /> : <ImageIcon className="h-5 w-5" />}
        </div>

        <div className="space-y-1">
          <p className="text-sm font-medium">
            {isDragging
              ? "Solte a imagem aqui"
              : hasPreview
                ? "Arraste uma nova imagem ou clique para selecionar"
                : "Arraste a imagem aqui ou clique para selecionar"}
          </p>
          <p className="text-xs text-muted-foreground">{hint}</p>
        </div>
      </div>
    </div>
  );
}
