# ADR-0008 – Modelo de Licenciamento e Multi-Tenant

## Status
Aceito

## Contexto
O sistema deve atender múltiplos clientes (empresas) com isolamento lógico, planos distintos e limitação de recursos.

## Decisão
Adotar modelo **Multi-Tenant com Isolamento Lógico**:
- Tenant = Empresa
- Usuários vinculados a Tenant
- Recursos limitados por plano:
  - Número de câmeras
  - Armazenamento
  - Recursos de IA

Licenciamento baseado em:
- Assinatura mensal
- Add-ons (IA, retenção estendida, alta disponibilidade)

## Consequências
- Facilita comercialização
- Complexidade adicional no controle de recursos

---