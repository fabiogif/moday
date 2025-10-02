"use client";

import { useState } from "react";
import { StatCards } from "./components/stat-cards";
import { DataTable } from "./components/data-table";
import { SuccessAlert } from "./components/success-alert";
import {
  useAuthenticatedTables,
  useMutation,
} from "@/hooks/use-authenticated-api";
import { endpoints } from "@/lib/api-client";
import { PageLoading } from "@/components/ui/loading-progress";

interface Table {
  id: number;
  identify: string;
  uuid: string;
  name: string;
  description?: string;
  capacity: number;
  created_at: string;
  created_at_formatted: string;
  updated_at: string;
}

interface TableFormValues {
  identify: string;
  name: string;
  description?: string;
  capacity: number;
}

export default function TablesPage() {
  const {
    data: tables,
    loading,
    error,
    refetch,
    isAuthenticated,
  } = useAuthenticatedTables();
  const { mutate: createTable, loading: creating } = useMutation();
  const { mutate: updateTable, loading: updating } = useMutation();
  const { mutate: deleteTable, loading: deleting } = useMutation();

  // Estados para o alert de sucesso
  const [successAlert, setSuccessAlert] = useState({
    open: false,
    title: "",
    message: "",
  });

  const handleAddTable = async (tableData: TableFormValues) => {
    try {
      const result = await createTable(
        endpoints.tables.create,
        "POST",
        tableData
      );

      if (result) {
        // Mostrar sucesso primeiro
        setSuccessAlert({
          open: true,
          title: "Sucesso!",
          message: "Mesa cadastrada com sucesso!",
        });
        // Recarregar dados após mostrar o alert
        setTimeout(async () => {
          await refetch();
        }, 100);
      }
    } catch (error) {
      console.error("Erro ao criar mesa:", error);
      alert("Erro ao salvar mesa. Tente novamente.");
    }
  };

  const handleDeleteTable = async (uuid: string) => {
    try {
      const result = await deleteTable(
        endpoints.tables.delete(uuid.toString()),
        "DELETE"
      );

      if (result) {
        // Recarregar dados após exclusão
        await refetch();
      }
    } catch (error) {
      console.error("Erro ao excluir mesa:", error);
      alert("Erro ao excluir mesa. Tente novamente.");
    }
  };

  const handleEditTable = async (id: number, tableData: TableFormValues) => {
    try {
      const result = await updateTable(
        endpoints.tables.update(id),
        "PUT",
        tableData
      );

      if (result) {
        // Mostrar sucesso primeiro
        setSuccessAlert({
          open: true,
          title: "Sucesso!",
          message: "Mesa atualizada com sucesso!",
        });
        // Recarregar dados após mostrar o alert
        setTimeout(async () => {
          await refetch();
        }, 100);
      }
    } catch (error) {
      console.error("Erro ao atualizar mesa:", error);
      alert("Erro ao atualizar mesa. Tente novamente.");
    }
  };

  const handleShowSuccessAlert = (title: string, message: string) => {
    setSuccessAlert({
      open: true,
      title,
      message,
    });
  };

  if (!isAuthenticated) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">
          Usuário não autenticado. Faça login para continuar.
        </div>
      </div>
    );
  }

  if (loading) {
    return <PageLoading isLoading={loading} message="Carregando mesas..." />;
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-destructive">Erro ao carregar mesas: {error}</div>
      </div>
    );
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <StatCards />
      </div>

      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable
          tables={Array.isArray(tables) ? tables : []}
          onDeleteTable={handleDeleteTable}
          onEditTable={handleEditTable}
          onAddTable={handleAddTable}
          onShowSuccessAlert={handleShowSuccessAlert}
        />
      </div>

      {/* Alert de sucesso */}
      <SuccessAlert
        open={successAlert.open}
        onOpenChange={(open: boolean) =>
          setSuccessAlert((prev) => ({ ...prev, open }))
        }
        title={successAlert.title}
        message={successAlert.message}
      />
    </div>
  );
}
