
let currentBalance = 10770;
const transactions = [
  { type: 'Deposit', amount: 200, date: '26/07/2024' },
  { type: 'Withdraw', amount: 777, date: '25/07/2024' },
  { type: 'Deposit', amount: 220, date: '24/07/2024' }
];

// Initialize transactions
document.addEventListener('DOMContentLoaded', () => {
  updateTransactionList();
});

// Deposit functionality
document.getElementById('depositButton').addEventListener('click', () => {
  processTransaction('Deposit');
});

// Withdraw functionality
document.getElementById('withdrawalButton').addEventListener('click', () => {
  processTransaction('Withdraw');
});

function processTransaction(type) {
  const amountInput = document.getElementById('amount');
  const amount = parseInt(amountInput.value);

  if (!amount || amount <= 0) {
    showError('Please enter a valid amount');
    return;
  }

  if (type === 'Withdraw' && amount > currentBalance) {
    showError('Insufficient funds');
    return;
  }

  currentBalance = type === 'Deposit' ? 
    currentBalance + amount : 
    currentBalance - amount;

  // Update balance display
  document.getElementById('balance').textContent = currentBalance;
  
  // Add transaction to history
  transactions.unshift({
    type,
    amount,
    date: new Date().toLocaleDateString('en-GB')
  });

  updateTransactionList();
  amountInput.value = '';
}

function updateTransactionList() {
  const tbody = document.getElementById('transactionList');
  tbody.innerHTML = transactions.map(transaction => `
    <tr>
      <td class="transaction-type ${transaction.type.toLowerCase()}">
        ${transaction.type}
      </td>
      <td>${transaction.amount}</td>
      <td>${transaction.date}</td>
    </tr>
  `).join('');
}

function showError(message) {
  const errorEl = document.createElement('div');
  errorEl.className = 'error-message';
  errorEl.textContent = message;
  
  document.body.appendChild(errorEl);
  setTimeout(() => errorEl.remove(), 3000);
}
