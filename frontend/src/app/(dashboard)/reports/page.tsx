"use client"

import { useState } from "react"
import { StatCards } from "./components/stat-cards"
import { DataTable } from "./components/data-table"

import initialReportsData from "./data.json"

interface Report {
  id: number
  name: string
  type: string
  description: string
  lastGenerated: string
  status: string
  isActive: boolean
  createdAt: string
}

interface ReportFormValues {
  name: string
  type: string
  description: string
  isActive: boolean
}

export default function ReportsPage() {
  const [reports, setReports] = useState<Report[]>(initialReportsData)

  const handleAddReport = (reportData: ReportFormValues) => {
    const newReport: Report = {
      id: Math.max(...reports.map(r => r.id)) + 1,
      name: reportData.name,
      type: reportData.type,
      description: reportData.description,
      lastGenerated: "",
      status: "Não Gerado",
      isActive: reportData.isActive,
      createdAt: new Date().toISOString().split('T')[0],
    }
    setReports(prev => [newReport, ...prev])
  }

  const handleDeleteReport = (id: number) => {
    setReports(prev => prev.filter(report => report.id !== id))
  }

  const handleEditReport = (report: Report) => {
    console.log("Edit report:", report)
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="@container/main px-4 lg:px-6">
        <StatCards />
      </div>
      
      <div className="@container/main px-4 lg:px-6 mt-8 lg:mt-12">
        <DataTable 
          reports={reports}
          onDeleteReport={handleDeleteReport}
          onEditReport={handleEditReport}
          onAddReport={handleAddReport}
        />
      </div>
    </div>
  )
}
