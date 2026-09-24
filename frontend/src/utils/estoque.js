export function statusEstoque(quantidadeAtual, estoqueMinimo, percentualAlerta = 20) {
  const minimo = Number(estoqueMinimo ?? 0)
  const atual  = Number(quantidadeAtual ?? 0)

  if (atual <= minimo) return 'critico'

  const limiteAlerta = minimo * (1 + Number(percentualAlerta ?? 20) / 100)
  if (atual <= limiteAlerta) return 'atencao'

  return 'ok'
}

export function badgeStatusEstoque(status) {
  const mapa = {
    critico: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400 px-2 py-1 rounded text-xs font-semibold',
    atencao: 'bg-yellow-100 dark:bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 px-2 py-1 rounded text-xs font-semibold',
    ok:      'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 px-2 py-1 rounded text-xs font-semibold',
  }
  return mapa[status] || mapa.ok
}

export function labelStatusEstoque(status) {
  const mapa = { critico: '↓ Crítico', atencao: '⚠ Atenção', ok: 'OK' }
  return mapa[status] || 'OK'
}