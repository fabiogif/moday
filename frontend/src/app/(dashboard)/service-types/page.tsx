"use client";

import { useState } from "react";
import React from "react";
import { StatCards } from "./components/stat-cards";
import { DataTable } from "./components/data-table";
import { ServiceTypeFormDialog } from "./components/service-type-form-dialog";
import { SuccessAlert } from "./components/success-alert";
import {
  useAuthenticatedServiceTypes,
  useMutation,
} from "@/hooks/use-authenticated-api";
import { endpoints } from "@/lib/api-client";
import { PageLoading } from "@/components/ui/loading-progress";
import { ServiceTypeData, ServiceTypeFormValues } from "./types";
import { useAuth } from "@/contexts/auth-context";

export default function ServiceTypesPage() {
  const {
    data: serviceTypes,
    loading,
    error,
    refetch,
    isAuthenticated,
  } = useAuthenticatedServiceTypes();
  const { isLoading: authLoading } = useAuth();
  const { mutate: createServiceType, loading: creating } = useMutation();
  const { mutate: updateServiceType, loading: updating } = useMutation();
  const { mutate: deleteServiceType, loading: deleting } = useMutation();

  // Estados para o alert de sucesso
  const [successAlert, setSuccessAlert] = useState({
    open: false,
    title: "",
    message: "",
  });

  // Estado para controlar tipo de atendimento sendo editado
  const [editingServiceType, setEditingServiceType] = useState<ServiceTypeData | null>(null);

  // Normalizar dados da API
  const normalizedServiceTypes: ServiceTypeData[] = React.useMemo(() => {
    if (!serviceTypes) return [];
    
    // Se for array direto
    if (Array.isArray(serviceTypes)) {
      return serviceTypes.map((st: any) => ({
        id: st.id,
        uuid: st.uuid || st.identify,
        identify: st.identify || st.uuid,
        name: st.name,
        slug: st.slug || "",
        description: st.description || "",
        is_active: st.is_active ?? true,
        requires_address: st.requires_address ?? false,
        requires_table: st.requires_table ?? false,
        available_in_menu: st.available_in_menu ?? false,
        order_position: st.order_position || 0,
        created_at: st.created_at || "",
        created_at_formatted: st.created_at_formatted || "",
        updated_at: st.updated_at || "",
      }));
    }
    
    // Se for objeto com propriedade data (Laravel Resource Collection)
    if (serviceTypes && typeof serviceTypes === 'object' && 'data' in serviceTypes) {
      const dataArray = (serviceTypes as any).data;
      if (Array.isArray(dataArray)) {
        return dataArray.map((st: any) => ({
          id: st.id,
          uuid: st.uuid || st.identify,
          identify: st.identify || st.uuid,
          name: st.name,
          slug: st.slug || "",
          description: st.description || "",
          is_active: st.is_active ?? true,
          requires_address: st.requires_address ?? false,
          requires_table: st.requires_table ?? false,
          available_in_menu: st.available_in_menu ?? false,
          order_position: st.order_position || 0,
          created_at: st.created_at || "",
          created_at_formatted: st.created_at_formatted || "",
          updated_at: st.updated_at || "",
        }));
      }
    }
    
    return [];
  }, [serviceTypes]);

  const handleAddServiceType = async (serviceTypeData: ServiceTypeFormValues) => {
    try {
      const result = await createServiceType(
        endpoints.serviceTypes.create,
        "POST",
        serviceTypeData
      );

      if (result) {
        // Mostrar sucesso primeiro
        setSuccessAlert({
          open: true,
          title: "Sucesso!",
          message: "Tipo de atendimento cadastrado com sucesso!",
        });
        // Recarregar dados após mostrar o alert
        setTimeout(async () => {
          await refetch();
        }, 100);
      }
    } catch (error) {
      setSuccessAlert({
        open: true,
        title: "Erro!",
        message: "Erro ao salvar tipo de atendimento. Tente novamente."
      });
    }
  };

  const handleEditServiceType = async (id: number, serviceTypeData: ServiceTypeFormValues) => {
    try {
      const result = await updateServiceType(
        endpoints.serviceTypes.update(id),
        "PUT",
        serviceTypeData
      );

      if (result) {
        setSuccessAlert({
          open: true,
          title: "Sucesso!",
          message: "Tipo de atendimento atualizado com sucesso!",
        });
        setEditingServiceType(null);
        setTimeout(async () => {
          await refetch();
        }, 100);
      }
    } catch (error) {
      setSuccessAlert({
        open: true,
        title: "Erro!",
        message: "Erro ao atualizar tipo de atendimento. Tente novamente."
      });
    }
  };

  const handleDeleteServiceType = async (identify: string) => {
    try {
      const result = await deleteServiceType(
        endpoints.serviceTypes.delete(identify),
        "DELETE"
      );

      if (result) {
        setSuccessAlert({
          open: true,
          title: "Sucesso!",
          message: "Tipo de atendimento removido com sucesso!",
        });
        setTimeout(async () => {
          await refetch();
        }, 100);
      }
    } catch (error) {
      setSuccessAlert({
        open: true,
        title: "Erro!",
        message: "Erro ao remover tipo de atendimento. Tente novamente."
      });
    }
  };

  const handleStartNew = () => {
    setEditingServiceType(null);
  };

  if (authLoading || loading) {
    return <PageLoading />;
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-center">
          <p className="text-lg font-semibold text-red-600">Erro ao carregar tipos de atendimento</p>
          <p className="text-muted-foreground mt-2">{error}</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-center">
          <p className="text-lg font-semibold">Você precisa estar autenticado para acessar esta página</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <div className="flex items-center justify-between space-y-2">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Tipos de Atendimento</h2>
          <p className="text-muted-foreground">
            Gerencie os tipos de atendimento disponíveis no sistema
          </p>
        </div>
        <ServiceTypeFormDialog
          onAddServiceType={handleAddServiceType}
          onEditServiceType={handleEditServiceType}
          editServiceType={editingServiceType}
          onStartNew={handleStartNew}
        />
      </div>

      <StatCards serviceTypes={normalizedServiceTypes} />

      <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
        <div className="p-6">
          <DataTable
            serviceTypes={normalizedServiceTypes}
            onDeleteServiceType={handleDeleteServiceType}
            onEditServiceType={setEditingServiceType}
            onAddServiceType={handleAddServiceType}
          />
        </div>
      </div>

      <SuccessAlert
        open={successAlert.open}
        onOpenChange={(open) => setSuccessAlert({ ...successAlert, open })}
        title={successAlert.title}
        message={successAlert.message}
      />
    </div>
  );
}

