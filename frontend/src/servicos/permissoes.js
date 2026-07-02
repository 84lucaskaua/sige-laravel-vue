// Fonte única de verdade sobre o que cada perfil pode acessar.
// Usado tanto no router (guard de navegação) quanto na sidebar (itens do menu).
// Se um perfil não estiver listado aqui, assume-se acesso total (ex: root/admin).

export const ROTAS_PERMITIDAS_POR_PERFIL = {
  visualizador: ['dashboard', 'produtos', 'relatorios', 'rel-avancados', 'perfil'],
}

/**
 * Verifica se um perfil pode acessar uma rota (pelo name da rota).
 * Perfis que não têm restrição explícita (ex: root) têm acesso liberado.
 */
export function perfilPodeAcessarRota(perfil, nomeRota) {
  const permitidas = ROTAS_PERMITIDAS_POR_PERFIL[perfil]
  if (!permitidas) return true // sem restrição definida = acesso liberado
  return permitidas.includes(nomeRota)
}