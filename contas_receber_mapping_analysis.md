# Contas a Receber Implementation Analysis and Mapping

## Current State Analysis

### What Already Exists:
1. **Migration**: `2024_12_11_012348_contas_receber.php` - Creates `contas_a_receber` table
2. **Model**: `ContasReceber.php` - Basic model with correct field mappings
3. **Database Structure**: Table already created with proper field mappings

### What's Missing:
1. **Filament Resource**: ContasReceberResource.php
2. **Parcela Model**: ContasReceberParcela model (to match ContasPagar structure)
3. **Parcela Migration**: Migration for `contas_receber_parcelas` table
4. **Relation Manager**: ParcelasRelationManager for ContasReceber
5. **Pages**: Create, Edit, List, View pages for ContasReceberResource

## Field Mappings (ContasPagar → ContasReceber)

### Core Mappings:
- `fornecedor` → `aluno` ✓ (already implemented)
- `pedido` (references compras) → `pedido` (references pedidos) ✓ (already implemented)
- `usuario` → `vendedor` ✓ (already implemented)

### Additional Fields Analysis:
- `empresa` → `empresa` (same)
- `recebimento` → `recebimento` (same)
- `valor` → `valor` (same)
- `desc` → `desc` (same)
- `acres` → `acres` (same)
- `descricao` → `descricao` (same)
- `status` → `status` (same)
- `meio_pgato` → `meio_pgato` (same)
- `parcela` → `parcela` (same)
- `total_parcelas` → `total_parcelas` (same)
- `parent_id` → `parent_id` (same)

### Structural Differences:
1. **ContasPagar** uses separate `ContasPagarParcela` model/table
2. **ContasReceber** currently uses parent/child relationship within same table
3. **Need to align**: Create ContasReceberParcela to match ContasPagar structure

## Implementation Plan

### Phase 1: Create Parcela Structure
1. Create `ContasReceberParcela` model
2. Create migration for `contas_receber_parcelas` table
3. Update `ContasReceber` model to use parcelas relationship like ContasPagar

### Phase 2: Create Filament Resource
1. Create `ContasReceberResource.php` based on ContasPagarResource
2. Create RelationManager for parcelas
3. Create all necessary pages (Create, Edit, List, View)

### Phase 3: Field Mappings in Resource
- Replace Fornecedor with Aluno in forms/tables
- Replace Compras with Pedidos in relationships
- Update labels and descriptions to reflect receivables context
- Ensure proper validation and business logic

### Phase 4: Testing and Validation
1. Test resource creation and management
2. Verify installment functionality
3. Ensure proper data relationships
