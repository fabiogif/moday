import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Moday",
  description: "Gestão de Restaurante",
};

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen bg-background">
      {children}
    </div>
  );
}
