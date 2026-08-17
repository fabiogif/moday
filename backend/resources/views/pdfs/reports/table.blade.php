<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; margin: 20px; font-size: 12px; }
  h1 { color: #333; font-size: 18px; margin-bottom: 10px; }
  .info { color: #666; font-size: 10px; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }
  th { background-color: #4CAF50; color: white; font-weight: bold; }
  tr:nth-child(even) { background-color: #f9f9f9; }
  .footer { margin-top: 20px; text-align: center; color: #999; font-size: 9px; }
</style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <div class="info">Gerado em: {{ $generatedAt }}</div>

  <table>
    <thead>
      <tr>
        @foreach ($columns as $column)
          <th>{{ $column }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $row)
        <tr>
          @foreach ($row as $cell)
            <td>{{ $cell ?? '-' }}</td>
          @endforeach
        </tr>
      @empty
        <tr>
          <td colspan="{{ max(count($columns), 1) }}" style="text-align:center;color:#999">
            Nenhum registro encontrado
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
