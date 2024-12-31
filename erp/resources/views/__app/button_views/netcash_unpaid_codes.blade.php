<div class="card">
    <div class="card-header">
    <h5>Unpaid Transaction Codes</h5>
    </div>
    <div class="card-body">
<table>
<tbody>
<tr>
<th>Unpaid Code</th>
<th>Brief Description</th>
<th>Full Description</th>
<th>Permanent Failure</th>
<th>Action</th>
</tr>
<tr>
<td style="text-align: center;">02</td>
<td style="text-align: left;">Not provided for</td>
<td style="text-align: left;">There were insufficient funds in this account. Please contact the customer to make arrangements for alternative payment or arrange to process a double payment on the next batch run.</td>
<td style="text-align: center;">No</td>
<td style="text-align: left;">You may process this transaction for a maximum of 2 (two) times.<br>
If you receive 2 (two) X code 02 rejections in a 45 (forty-five calendar day) period, -you will require a <b>new mandate</b> as the consecutive transactions will return <a href="#60">code 60</a> and could impose an additional penalty fee.</td>
</tr>
<tr>
<td style="text-align: center;">03</td>
<td style="text-align: left;">Debits not allowed to this account</td>
<td style="text-align: left;">The customers’ account is a special savings account and cannot be debited. Kindly request customer to provide alternative banking details account details or change the account to a Transmission Account.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">04</td>
<td style="text-align: left;">Payment Stopped (by a/c holder)</td>
<td style="text-align: left;">Your customer has requested that payment be stopped and the debit order be reversed. Please contact your customer to query the Stop Payment. No further debit order instruction will run against this account until Stop Payment has been lifted.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">06</td>
<td style="text-align: left;">Account Frozen (as in divorce, etc)</td>
<td style="text-align: left;">Your customers account has been frozen due to legal proceedings Please contact your customer to obtain alternative banking details. No further debit orders will be run against this account until legal proceedings have been completed.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">08</td>
<td style="text-align: left;">Account in sequestration (private individual)</td>
<td style="text-align: left;">Your customers account has been frozen due to legal proceedings Please contact your customer to obtain alternative banking details as no further debit orders will be run against this account.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">10</td>
<td style="text-align: left;">Account in liquidation (company)</td>
<td style="text-align: left;">Your customers account has been frozen due to legal proceedings Please contact your customer to obtain alternative banking details as no further debit orders will be run against this account.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">12</td>
<td style="text-align: left;">Account closed (with no forwarding details)</td>
<td style="text-align: left;">Your customers account has been closed. Kindly contact your customer to obtain alternative banking details as no further debit orders can be run against this account.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">14</td>
<td style="text-align: left;">Account transferred</td>
<td style="text-align: left;">Account transferred within the same banking group</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">16</td>
<td style="text-align: left;">Account transferred (to another banking group)</td>
<td style="text-align: left;">Account transferred to another banking group</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">18</td>
<td style="text-align: left;">Account holder deceased</td>
<td style="text-align: left;">You customers’ account has been closed as the account holder is deceased. Please obtain alternative banking details if the debit is to continue or contact the customers’ attorneys for settlement</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">22</td>
<td style="text-align: left;">Account effects not cleared</td>
<td style="text-align: left;">Your customers’ account had funds but these were not cleared in time for the debit order to be processed. Please contact you customer to arrange manual payment or confirm when your debit order can be resubmitted</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">26</td>
<td style="text-align: left;">No such account</td>
<td style="text-align: left;">Your customers’ account may be invalid or has been closed. Please contact your customer to obtain alternative banking details.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.<a name="code30"></a></td>
</tr>
<tr>
<td style="text-align: center;">28</td>
<td style="text-align: left;">Recall/Withdrawal</td>
<td style="text-align: left;">Your customer has requested a withdrawal of the debit order entry.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">30</td>
<td style="text-align: left;">No authority to debit / credit</td>
<td style="text-align: left;">Your customer has stopped payment of the debit order, allegedly due to you not having authority to debit the account. Please contact your customer to resolve the dispute as no further debit orders will be submitted on this account until stop payment is lifted.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">32</td>
<td style="text-align: left;">Debit in contravention of payer’s authority</td>
<td style="text-align: left;">Your customer has stopped payment of the debit order which allegedly is in contravention of the agreement entered into. Please contact your customer to resolve the dispute. No further debit orders can be submitted on this account until stop payment is lifted.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">34</td>
<td style="text-align: left;">Authorization cancelled</td>
<td style="text-align: left;">Your customer has stopped payment on the debit order, and cancelled the authorization. Please contact your customer to resolve the dispute as no further debit orders will be submitted on this account until stop payment is lifted.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">36</td>
<td style="text-align: left;">Previously stopped via stop payment advice</td>
<td style="text-align: left;">Your customer has previously stopped payment of the debit order due to a dispute. Please contact your customer to resolve the dispute as no further debit orders will be submitted on this account until stop payment is lifted.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">50</td>
<td style="text-align: left;">Account Number Invalid</td>
<td style="text-align: left;">Your customers account is invalid. Please contact your customer to obtain alternative banking details.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">51</td>
<td style="text-align: left;">Bank Recall</td>
<td style="text-align: left;">The bank has requested a recall on this debit order transaction entry. Please contact your customer to arrange settlement and resubmit if necessary.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">56</td>
<td style="text-align: left;">Not FICA compliant</td>
<td style="text-align: left;">This account is not FICA compliant and the bank will not allow any further transactions to this account until your customer complies to the requirements. Please make alternative arrangements for payment with your client.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;"><a name="60"></a>Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">60</td>
<td style="text-align: left;">Consecutive Unpaid Rejection</td>
<td style="text-align: left;">There have been consecutive unpaid transactions against this account number. Remove the transaction from all future batches.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again. Re-processing a code 60 transaction could impose an additional penalty fee.<a name="cc"></a></td>
</tr>
<tr>
<td style="text-align: center;">145</td>
<td style="text-align: left;">Account failed final validation</td>
<td style="text-align: left;">This is a credit payment error when an account fails the final validation but still exists in the re-submit if necessary.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td colspan="5" align="justify">
<h4><b><br>
Credit Card Specific Unpaid Reason Codes</b></h4>
<p><sup>What is an <a href="#what">Unpaid Transaction Code</a>?</sup></p></td>
</tr>
<tr>
<td style="text-align: center;">81</td>
<td style="text-align: left;">Credit Card Token Error</td>
<td style="text-align: left;">This transaction has been declined because there is a likely error in the token. Please get a new token for this card number and then rerun the transaction.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new token</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">82</td>
<td style="text-align: left;">Credit Card Declined – Please Call</td>
<td style="text-align: left;">This transaction has been declined because the bank needs a call to authorise the transaction. Please contact the customer and ask them to contact the bank with regards to the transaction and then rerun the transaction.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed. Customer must contact his/her bank to lift the hold.</td>
</tr>
<tr>
<td style="text-align: center;">83</td>
<td style="text-align: left;">Credit Card Timeout</td>
<td style="text-align: left;">This transaction has timed out on the bank credit card switch, but has not been declined. It has thus not reached the intended customer account. Please run this again.</td>
<td style="text-align: center;">No</td>
<td style="text-align: left;">Further processing allowed. Process this transaction again or customer to contact the bank.</td>
</tr>
<tr>
<td style="text-align: center;">84</td>
<td style="text-align: left;">Credit Card Declined – Reported As Stolen</td>
<td style="text-align: left;">This transaction has been declined because the card has been reported stolen. Please contact the customer to get new details to process any additional transactions</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">85</td>
<td style="text-align: left;">Credit Card Declined – Account Closed</td>
<td style="text-align: left;">This transaction has been declined because the account has been closed. Please contact the customer to get new details to process any additional transactions</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">86</td>
<td style="text-align: left;">Credit Card Declined – Disputed by cardholder</td>
<td style="text-align: left;">This transaction has been declined because it has been disputed by cardholder. Please contact the customer to get new expiry date and process the transaction again.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">87</td>
<td style="text-align: left;">Credit Card Declined – Invalid number</td>
<td style="text-align: left;">This transaction has been declined because it is an invalid card number. Please contact the customer to verify the details process the transaction again.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">88</td>
<td style="text-align: left;">Credit Card Declined – Expired</td>
<td style="text-align: left;">This transaction has been declined because the card has expired. Please contact the customer to get new expiry date and process the transaction again.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">89</td>
<td style="text-align: left;">Credit Card Declined</td>
<td style="text-align: left;">This transaction has been declined by the credit card company. Please contact the customer to make arrangements for alternative payment or arrange to process a double payment on the next batch run.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
<tr>
<td style="text-align: center;">90</td>
<td style="text-align: left;">Non Possession of card</td>
<td style="text-align: left;">Gateway return with a reason of Non Possession of card is usually related to a return that was done because the card was used fraudulently.</td>
<td style="text-align: center;">Yes</td>
<td style="text-align: left;">Further processing not allowed, you now require a <b>new mandate</b> should you wish to process this transaction again.</td>
</tr>
</tbody>
</table>
    </div>   
</div>