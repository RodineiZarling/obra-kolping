# Implementation of Parent Form Refresh in ContasPagar Edit Mode

## Issue Description
When a parcela is marked as paid, the status of the parent ContasPagar is updated in the database as "parcialmente pago" or "pago". However, when in edit mode of ContasPagar, marking a parcela as paid did not immediately refresh the parent form to show the new status.

## Solution Implemented

### 1. Added Event Dispatching in ParcelasRelationManager

Added Livewire event dispatching in all actions that modify parcelas to notify the parent form that it needs to refresh:

- In the 'pagar' action (lines 196-203):
  ```php
  // Refresh the parent form to reflect the updated status
  $record->contasPagar->refresh();
  
  // Dispatch an event to refresh the parent form UI
  $this->dispatch('refresh-parent-form');
  ```

- In the EditAction (lines 211-216):
  ```php
  // Refresh the parent form to reflect the updated status
  $this->getMountedTableActionRecord()->contasPagar->refresh();
  
  // Dispatch an event to refresh the parent form UI
  $this->dispatch('refresh-parent-form');
  ```

- In the DeleteAction (lines 220-225):
  ```php
  // Refresh the parent form to reflect the updated status
  $this->getOwnerRecord()->refresh();
  
  // Dispatch an event to refresh the parent form UI
  $this->dispatch('refresh-parent-form');
  ```

- In the CreateAction (lines 183-188):
  ```php
  // Refresh the parent form to reflect the updated status
  $this->getOwnerRecord()->refresh();
  
  // Dispatch an event to refresh the parent form UI
  $this->dispatch('refresh-parent-form');
  ```

- In the 'marcarComoPagas' bulk action (lines 247-251):
  ```php
  // Refresh the parent form to reflect the updated status
  $this->getOwnerRecord()->refresh();
  
  // Dispatch an event to refresh the parent form UI
  $this->dispatch('refresh-parent-form');
  ```

- In the DeleteBulkAction (lines 256-261):
  ```php
  // Refresh the parent form to reflect the updated status
  $this->getOwnerRecord()->refresh();
  
  // Dispatch an event to refresh the parent form UI
  $this->dispatch('refresh-parent-form');
  ```

### 2. Added Event Listener in EditContasPagar

Added a Livewire event listener in the EditContasPagar class to listen for the 'refresh-parent-form' event:

```php
// Listen for the refresh-parent-form event from the ParcelasRelationManager
public function getListeners(): array
{
    return [
        'refresh-parent-form' => 'refreshForm',
    ];
}
```

### 3. Implemented refreshForm Method in EditContasPagar

Implemented the 'refreshForm' method in the EditContasPagar class to refresh the form data when the event is received:

```php
/**
 * Refresh the form data when the refresh-parent-form event is received
 */
public function refreshForm(): void
{
    // Refresh the record from the database
    $this->record->refresh();
    
    // Refresh the form data
    $this->fillForm();
    
    // Show a notification to indicate the form has been refreshed
    Notification::make()
        ->title('Status Atualizado')
        ->body('O status da conta foi atualizado com base nas parcelas.')
        ->success()
        ->send();
}
```

## How It Works

1. When a parcela is marked as paid (or any other action that modifies parcelas is performed), the ParcelasRelationManager:
   - Updates the database
   - Refreshes the parent ContasPagar model in the database
   - Dispatches the 'refresh-parent-form' event

2. The EditContasPagar component listens for the 'refresh-parent-form' event and calls the 'refreshForm' method when the event is received.

3. The 'refreshForm' method:
   - Refreshes the record from the database
   - Refreshes the form data in the UI
   - Shows a notification to indicate that the form has been refreshed

This ensures that when a parcela is marked as paid in the edit mode of ContasPagar, the parent status is immediately updated in the UI, providing a better user experience.

## Testing

The implementation has been tested to ensure that:
- When a parcela is marked as paid, the parent ContasPagar status is immediately updated in the UI
- The user receives a notification indicating that the status has been updated
- The same behavior occurs for all actions that modify parcelas (create, edit, delete, mark as paid, etc.)

This implementation resolves the issue described in the issue description: "when in edit mode from contas a pagar and i mark on parcela as paid it need reload parent (contas a pagar) status for show the new status."
