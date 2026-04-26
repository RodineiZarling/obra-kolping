# Implementation of "Vencido" Status for Parcelas

## Changes Made

### 1. Updated ParcelasRelationManager.php

- Added the new status "4 - Vencido" to the form options
- Added "Vencido" to the status column definition with red color
- Added "Vencido" to the status filter options
- Added code to call `updateAllOverdueStatus()` when the parcelas table is displayed

### 2. Enhanced ContasPagarParcela.php Model

- Added `isOverdue()` method to check if a parcela is overdue:
  - Due date is before the current date
  - Payment date (recebimento) is empty
  - Status is not already "Cancelado" (3)
  
- Added `updateOverdueStatus()` method to set status to "Vencido" (4) if overdue

- Added logic in the `saving` event handler to automatically set status to "Vencido" (4) when a parcela is overdue

- Added static `updateAllOverdueStatus()` method to update all existing parcelas that meet the overdue criteria

### 3. Created Tests

- Created test cases to verify that:
  - Parcelas with past due dates are correctly marked as overdue
  - Parcelas with future due dates remain "Em aberto"
  - Parcelas that are already paid remain "Pago"
  - The `isOverdue()` method correctly identifies which parcelas are overdue

## Requirements Satisfied

The implementation satisfies all requirements from the issue description:

1. ✅ Added new status "4 - vencido" with red color
2. ✅ Status is automatically set when:
   - The due date (vencimento) is before the current date
   - The payment date (recebimento) is empty (not paid)

## How It Works

1. When a parcela is saved, it checks if it's overdue and updates the status accordingly
2. When the parcelas table is displayed, it automatically updates all parcelas that have become overdue
3. The status is displayed with a red badge in the UI
4. Users can filter parcelas by the "Vencido" status

This implementation ensures that parcelas are automatically marked as overdue when appropriate, providing better visibility of overdue payments in the system.
