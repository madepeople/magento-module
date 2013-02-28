<?php

if (!class_exists("CreateOrder",false)) {
/**
 * CreateOrder
 */
class CreateOrder {
	/**
	 * @access public
	 * @var OrderRequest
	 */
	public $request;
}}

if (!class_exists("BasicRequest",false)) {
/**
 * BasicRequest
 */
class BasicRequest {
	/**
	 * @access public
	 * @var ClientAuthInfo
	 */
	public $Auth;
}}

if (!class_exists("ClientAuthInfo",false)) {
/**
 * ClientAuthInfo
 */
class ClientAuthInfo {
	/**
	 * @access public
	 * @var sint
	 */
	public $ClientNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Username;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Password;
}}

if (!class_exists("ClientOrderInfo",false)) {
/**
 * ClientOrderInfo
 */
class ClientOrderInfo {
	/**
	 * @access public
	 * @var sstring
	 */
	public $ClientOrderNr;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerReference;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $OrderDate;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CountryCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerEmail;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $IsCompany;
	/**
	 * @access public
	 * @var slong
	 */
	public $PreApprovedCustomerId;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressSelector;
}}

if (!class_exists("ClientInvoiceRowInfo",false)) {
/**
 * ClientInvoiceRowInfo
 */
class ClientInvoiceRowInfo {
	/**
	 * @access public
	 * @var sstring
	 */
	public $ArticleNr;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Description;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $PricePerUnit;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $NrOfUnits;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Unit;
	/**
	 * @access public
	 * @var sint
	 */
	public $VatPercent;
	/**
	 * @access public
	 * @var sint
	 */
	public $DiscountPercent;
	/**
	 * @access public
	 * @var sint
	 */
	public $ClientOrderRowNr;
}}

if (!class_exists("CreateOrderResponse",false)) {
/**
 * CreateOrderResponse
 */
class CreateOrderResponse {
	/**
	 * @access public
	 * @var OrderResponse
	 */
	public $CreateOrderResult;
}}

if (!class_exists("BasicResponse",false)) {
/**
 * BasicResponse
 */
class BasicResponse {
	/**
	 * @access public
	 * @var sboolean
	 */
	public $Accepted;
	/**
	 * @access public
	 * @var sstring
	 */
	public $ErrorMessage;
}}

if (!class_exists("OrderRejectionCode",false)) {
/**
 * OrderRejectionCode
 */
class OrderRejectionCode {
}}

if (!class_exists("CreditReportCustomer",false)) {
/**
 * CreditReportCustomer
 */
class CreditReportCustomer {
	/**
	 * @access public
	 * @var sstring
	 */
	public $LegalName;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $PhoneNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressLine1;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressLine2;
	/**
	 * @access public
	 * @var sint
	 */
	public $Postcode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Postarea;
	/**
	 * @access public
	 * @var tnsBusinessTypeCode
	 */
	public $BusinessType;
}}

if (!class_exists("BusinessTypeCode",false)) {
/**
 * BusinessTypeCode
 */
class BusinessTypeCode {
}}

if (!class_exists("ChangeOrderAmount",false)) {
/**
 * ChangeOrderAmount
 */
class ChangeOrderAmount {
	/**
	 * @access public
	 * @var ChangeOrderAmountRequest
	 */
	public $request;
}}

if (!class_exists("ChangeOrderAmountRequest",false)) {
/**
 * ChangeOrderAmountRequest
 */
class ChangeOrderAmountRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
	/**
	 * @access public
	 * @var ArrayOfClientInvoiceRowInfo
	 */
	public $RemainingInvoiceRows;
}}

if (!class_exists("ChangeOrderAmountResponse",false)) {
/**
 * ChangeOrderAmountResponse
 */
class ChangeOrderAmountResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsChangeOrderAmountRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $NewCreditCheckDone;
	/**
	 * @access public
	 * @var slong
	 */
	public $AuthorizeId;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $AuthorizedAmount;
	/**
	 * @access public
	 * @var CreditReportCustomer
	 */
	public $ValidCustomer;
}}

if (!class_exists("ChangeOrderAmountRejectionCode",false)) {
/**
 * ChangeOrderAmountRejectionCode
 */
class ChangeOrderAmountRejectionCode {
}}

if (!class_exists("CreateInvoice",false)) {
/**
 * CreateInvoice
 */
class CreateInvoice {
	/**
	 * @access public
	 * @var CreateInvoiceRequest
	 */
	public $request;
}}

if (!class_exists("CreateInvoiceRequest",false)) {
/**
 * CreateInvoiceRequest
 */
class CreateInvoiceRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
	/**
	 * @access public
	 * @var ClientInvoiceInfo
	 */
	public $Invoice;
}}

if (!class_exists("ClientInvoiceInfo",false)) {
/**
 * ClientInvoiceInfo
 */
class ClientInvoiceInfo {
	/**
	 * @access public
	 * @var sint
	 */
	public $NumberOfCreditDays;
	/**
	 * @access public
	 * @var tnsInvoiceDistributionCode
	 */
	public $InvoiceDistributionForm;
	/**
	 * @access public
	 * @var slong
	 */
	public $InvoiceNrToCredit;
	/**
	 * @access public
	 * @var ArrayOfClientInvoiceRowInfo
	 */
	public $InvoiceRows;
}}

if (!class_exists("InvoiceDistributionCode",false)) {
/**
 * InvoiceDistributionCode
 */
class InvoiceDistributionCode {
}}

if (!class_exists("CreateInvoiceResponse",false)) {
/**
 * CreateInvoiceResponse
 */
class CreateInvoiceResponse extends BasicResponse {
	/**
	 * @access public
	 * @var sstring
	 */
	public $BoughtInvoiceText;
	/**
	 * @access public
	 * @var sstring
	 */
	public $OcrPaymentAccountNumber;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InvoiceAmount;
	/**
	 * @access public
	 * @var sstring
	 */
	public $InvoiceAmountCheckDigit;
	/**
	 * @access public
	 * @var sstring
	 */
	public $OcrReference;
	/**
	 * @access public
	 * @var tnsInvoiceRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var slong
	 */
	public $CustomerId;
	/**
	 * @access public
	 * @var slong
	 */
	public $InvoiceNumber;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $InvoiceDate;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $DueDate;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $PdfLinkIncluded;
	/**
	 * @access public
	 * @var sstring
	 */
	public $PdfLink;
}}

if (!class_exists("InvoiceRejectionCode",false)) {
/**
 * InvoiceRejectionCode
 */
class InvoiceRejectionCode {
}}

if (!class_exists("ChangeOrderInfo",false)) {
/**
 * ChangeOrderInfo
 */
class ChangeOrderInfo {
	/**
	 * @access public
	 * @var ChangeOrderInfoRequest
	 */
	public $request;
}}

if (!class_exists("ChangeOrderInfoRequest",false)) {
/**
 * ChangeOrderInfoRequest
 */
class ChangeOrderInfoRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $ChangeClientOrderNr;
	/**
	 * @access public
	 * @var sstring
	 */
	public $NewClientOrderNr;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $ChangeCustomerReference;
	/**
	 * @access public
	 * @var sstring
	 */
	public $NewCustomerReference;
}}

if (!class_exists("ChangeOrderInfoResponse",false)) {
/**
 * ChangeOrderInfoResponse
 */
class ChangeOrderInfoResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsChangeOrderInfoRejectionCode
	 */
	public $RejectionCode;
}}

if (!class_exists("ChangeOrderInfoRejectionCode",false)) {
/**
 * ChangeOrderInfoRejectionCode
 */
class ChangeOrderInfoRejectionCode {
}}

if (!class_exists("CloseOrder",false)) {
/**
 * CloseOrder
 */
class CloseOrder {
	/**
	 * @access public
	 * @var CloseOrderRequest
	 */
	public $request;
}}

if (!class_exists("CloseOrderRequest",false)) {
/**
 * CloseOrderRequest
 */
class CloseOrderRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
}}

if (!class_exists("CloseOrderResponse",false)) {
/**
 * CloseOrderResponse
 */
class CloseOrderResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsCloseOrderRejectionCode
	 */
	public $RejectionCode;
}}

if (!class_exists("CloseOrderRejectionCode",false)) {
/**
 * CloseOrderRejectionCode
 */
class CloseOrderRejectionCode {
}}

if (!class_exists("GetOrders",false)) {
/**
 * GetOrders
 */
class GetOrders {
	/**
	 * @access public
	 * @var GetOrdersRequest
	 */
	public $request;
}}

if (!class_exists("GetOrdersRequest",false)) {
/**
 * GetOrdersRequest
 */
class GetOrdersRequest extends BasicRequest {
	/**
	 * @access public
	 * @var sstring
	 */
	public $ClientOrderNr;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $ClientOrderNrIncluded;
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $SveaOrderNrIncluded;
}}

if (!class_exists("GetOrdersResponse",false)) {
/**
 * GetOrdersResponse
 */
class GetOrdersResponse extends BasicResponse {
	/**
	 * @access public
	 * @var ArrayOfOrderStatus
	 */
	public $Orders;
	/**
	 * @access public
	 * @var tnsGetOrdersRejectionCode
	 */
	public $RejectionCode;
}}

if (!class_exists("OrderStatus",false)) {
/**
 * OrderStatus
 */
class OrderStatus {
	/**
	 * @access public
	 * @var sstring
	 */
	public $ClientOrderNr;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $IsActive;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $CreationDate;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $ClosedDate;
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $WillBuyInvoices;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $CurrentAmount;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $ExpirationDate;
	/**
	 * @access public
	 * @var CreditReportCustomer
	 */
	public $ValidCustomer;
	/**
	 * @access public
	 * @var ArrayOfInvoiceStatus
	 */
	public $Invoices;
}}

if (!class_exists("InvoiceStatus",false)) {
/**
 * InvoiceStatus
 */
class InvoiceStatus {
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $CreationDate;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InvoiceAmountWithoutVat;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InvoiceVat;
	/**
	 * @access public
	 * @var slong
	 */
	public $CustomerId;
	/**
	 * @access public
	 * @var slong
	 */
	public $InvoiceNumber;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $InvoiceDate;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $DueDate;
}}

if (!class_exists("GetOrdersRejectionCode",false)) {
/**
 * GetOrdersRejectionCode
 */
class GetOrdersRejectionCode {
}}

if (!class_exists("CheckInternalScoring",false)) {
/**
 * CheckInternalScoring
 */
class CheckInternalScoring {
	/**
	 * @access public
	 * @var CheckInternalScoringRequest
	 */
	public $request;
}}

if (!class_exists("CheckInternalScoringRequest",false)) {
/**
 * CheckInternalScoringRequest
 */
class CheckInternalScoringRequest extends BasicRequest {
	/**
	 * @access public
	 * @var sstring
	 */
	public $CountryCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $IsCompany;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $Amount;
}}

if (!class_exists("CheckInternalScoringResponse",false)) {
/**
 * CheckInternalScoringResponse
 */
class CheckInternalScoringResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsCheckInternalScoringRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var slong
	 */
	public $CreditDecisionId;
}}

if (!class_exists("CheckInternalScoringRejectionCode",false)) {
/**
 * CheckInternalScoringRejectionCode
 */
class CheckInternalScoringRejectionCode {
}}

if (!class_exists("CreatePaymentPlan",false)) {
/**
 * CreatePaymentPlan
 */
class CreatePaymentPlan {
	/**
	 * @access public
	 * @var CreatePaymentPlanRequest
	 */
	public $request;
}}

if (!class_exists("CreatePaymentPlanRequest",false)) {
/**
 * CreatePaymentPlanRequest
 */
class CreatePaymentPlanRequest extends BasicRequest {
	/**
	 * @access public
	 * @var ArrayOfClientInvoiceRowInfo
	 */
	public $InvoiceRows;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $Amount;
	/**
	 * @access public
	 * @var ClientPaymentPlanInfo
	 */
	public $PayPlan;
}}

if (!class_exists("ClientPaymentPlanInfo",false)) {
/**
 * ClientPaymentPlanInfo
 */
class ClientPaymentPlanInfo {
	/**
	 * @access public
	 * @var sboolean
	 */
	public $SendAutomaticGiropaymentForm;
	/**
	 * @access public
	 * @var slong
	 */
	public $CampainCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $ClientPaymentPlanNr;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerReference;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CountryCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerEmail;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerPhoneNumber;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $IsCompany;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumberCoApplicant;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerEmailCoApplicant;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CustomerPhoneNumberCoApplicant;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressSelector;
}}

if (!class_exists("CreatePaymentPlanResponse",false)) {
/**
 * CreatePaymentPlanResponse
 */
class CreatePaymentPlanResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsCreatePaymentPlanRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaPaymentPlanNr;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $AuthorizedAmount;
	/**
	 * @access public
	 * @var slong
	 */
	public $ContractNumber;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $ContractNumberIncluded;
	/**
	 * @access public
	 * @var CreditReportCustomer
	 */
	public $ValidCustomer;
	/**
	 * @access public
	 * @var CreditReportCustomer
	 */
	public $ValidCustomerCoApplicant;
}}

if (!class_exists("CreatePaymentPlanRejectionCode",false)) {
/**
 * CreatePaymentPlanRejectionCode
 */
class CreatePaymentPlanRejectionCode {
}}

if (!class_exists("GetPaymentPlanStatus",false)) {
/**
 * GetPaymentPlanStatus
 */
class GetPaymentPlanStatus {
	/**
	 * @access public
	 * @var GetPaymentPlanStatusRequest
	 */
	public $request;
}}

if (!class_exists("GetPaymentPlanStatusRequest",false)) {
/**
 * GetPaymentPlanStatusRequest
 */
class GetPaymentPlanStatusRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaPaymentPlanNr;
}}

if (!class_exists("GetPaymentPlanStatusResponse",false)) {
/**
 * GetPaymentPlanStatusResponse
 */
class GetPaymentPlanStatusResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsGetPaymentPlanStatusRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var tnsGetPaymentPlanResponseStatus
	 */
	public $Status;
	/**
	 * @access public
	 * @var slong
	 */
	public $CampainCode;
}}

if (!class_exists("GetPaymentPlanStatusRejectionCode",false)) {
/**
 * GetPaymentPlanStatusRejectionCode
 */
class GetPaymentPlanStatusRejectionCode {
}}

if (!class_exists("GetPaymentPlanResponseStatus",false)) {
/**
 * GetPaymentPlanResponseStatus
 */
class GetPaymentPlanResponseStatus {
}}

if (!class_exists("CancelPaymentPlan",false)) {
/**
 * CancelPaymentPlan
 */
class CancelPaymentPlan {
	/**
	 * @access public
	 * @var CancelPaymentPlanRequest
	 */
	public $request;
}}

if (!class_exists("CancelPaymentPlanRequest",false)) {
/**
 * CancelPaymentPlanRequest
 */
class CancelPaymentPlanRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaPaymentPlanNr;
}}

if (!class_exists("CancelPaymentPlanResponse",false)) {
/**
 * CancelPaymentPlanResponse
 */
class CancelPaymentPlanResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsCancelPaymentPlanRejectionCode
	 */
	public $RejectionCode;
}}

if (!class_exists("CancelPaymentPlanRejectionCode",false)) {
/**
 * CancelPaymentPlanRejectionCode
 */
class CancelPaymentPlanRejectionCode {
}}

if (!class_exists("ApprovePaymentPlan",false)) {
/**
 * ApprovePaymentPlan
 */
class ApprovePaymentPlan {
	/**
	 * @access public
	 * @var ApprovePaymentPlanRequest
	 */
	public $request;
}}

if (!class_exists("ApprovePaymentPlanRequest",false)) {
/**
 * ApprovePaymentPlanRequest
 */
class ApprovePaymentPlanRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaPaymentPlanNr;
}}

if (!class_exists("ApprovePaymentPlanResponse",false)) {
/**
 * ApprovePaymentPlanResponse
 */
class ApprovePaymentPlanResponse extends BasicResponse {
	/**
	 * @access public
	 * @var slong
	 */
	public $ContractNumber;
	/**
	 * @access public
	 * @var tnsApprovePaymentPlanRejectionCode
	 */
	public $RejectionCode;
}}

if (!class_exists("ApprovePaymentPlanRejectionCode",false)) {
/**
 * ApprovePaymentPlanRejectionCode
 */
class ApprovePaymentPlanRejectionCode {
}}

if (!class_exists("GetPaymentPlanOptions",false)) {
/**
 * GetPaymentPlanOptions
 */
class GetPaymentPlanOptions {
	/**
	 * @access public
	 * @var GetPaymentPlanOptionsRequest
	 */
	public $request;
}}

if (!class_exists("GetPaymentPlanOptionsRequest",false)) {
/**
 * GetPaymentPlanOptionsRequest
 */
class GetPaymentPlanOptionsRequest extends BasicRequest {
	/**
	 * @access public
	 * @var sdouble
	 */
	public $Amount;
	/**
	 * @access public
	 * @var ArrayOfClientInvoiceRowInfo
	 */
	public $InvoiceRows;
}}

if (!class_exists("GetPaymentPlanOptionsResponse",false)) {
/**
 * GetPaymentPlanOptionsResponse
 */
class GetPaymentPlanOptionsResponse extends BasicResponse {
	/**
	 * @access public
	 * @var ArrayOfPaymentPlanOption
	 */
	public $PaymentPlanOptions;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $Amount;
}}

if (!class_exists("PaymentPlanOption",false)) {
/**
 * PaymentPlanOption
 */
class PaymentPlanOption {
	/**
	 * @access public
	 * @var slong
	 */
	public $CampainCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Description;
	/**
	 * @access public
	 * @var tnsPaymentPlanTypeCode
	 */
	public $PaymentPlanType;
	/**
	 * @access public
	 * @var sint
	 */
	public $ContractLengthInMonths;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $MonthlyAnnuity;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InitialFee;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $NotificationFee;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InterestRatePercent;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $EffectiveInterestRatePercent;
	/**
	 * @access public
	 * @var sint
	 */
	public $NrOfInterestFreeMonths;
	/**
	 * @access public
	 * @var sint
	 */
	public $NrOfPaymentFreeMonths;
}}

if (!class_exists("PaymentPlanTypeCode",false)) {
/**
 * PaymentPlanTypeCode
 */
class PaymentPlanTypeCode {
}}

if (!class_exists("GetContractPdf",false)) {
/**
 * GetContractPdf
 */
class GetContractPdf {
	/**
	 * @access public
	 * @var GetContractPdfRequest
	 */
	public $request;
}}

if (!class_exists("GetContractPdfRequest",false)) {
/**
 * GetContractPdfRequest
 */
class GetContractPdfRequest extends BasicRequest {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaPaymentPlanNr;
}}

if (!class_exists("GetContractPdfResponse",false)) {
/**
 * GetContractPdfResponse
 */
class GetContractPdfResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsGetContractPdfRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var slong
	 */
	public $FileLengthInBytes;
	/**
	 * @access public
	 * @var sstring
	 */
	public $FileBinaryDataBase64;
	/**
	 * @access public
	 * @var sstring
	 */
	public $PdfLink;
}}

if (!class_exists("GetContractPdfRejectionCode",false)) {
/**
 * GetContractPdfRejectionCode
 */
class GetContractPdfRejectionCode {
}}

if (!class_exists("AddToBlockList",false)) {
/**
 * AddToBlockList
 */
class AddToBlockList {
	/**
	 * @access public
	 * @var AddToBlockListRequest
	 */
	public $request;
}}

if (!class_exists("AddToBlockListRequest",false)) {
/**
 * AddToBlockListRequest
 */
class AddToBlockListRequest extends BasicRequest {
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $IsCompany;
	/**
	 * @access public
	 * @var sstring
	 */
	public $ReasonForBlock;
}}

if (!class_exists("AddToBlockListResponse",false)) {
/**
 * AddToBlockListResponse
 */
class AddToBlockListResponse extends BasicResponse {
}}

if (!class_exists("GetAddresses",false)) {
/**
 * GetAddresses
 */
class GetAddresses {
	/**
	 * @access public
	 * @var GetCustomerAddressesRequest
	 */
	public $request;
}}

if (!class_exists("GetCustomerAddressesRequest",false)) {
/**
 * GetCustomerAddressesRequest
 */
class GetCustomerAddressesRequest extends BasicRequest {
	/**
	 * @access public
	 * @var sboolean
	 */
	public $IsCompany;
	/**
	 * @access public
	 * @var sstring
	 */
	public $CountryCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
}}

if (!class_exists("GetAddressesResponse",false)) {
/**
 * GetAddressesResponse
 */
class GetAddressesResponse {
	/**
	 * @access public
	 * @var GetCustomerAddressesResponse
	 */
	public $GetAddressesResult;
}}

if (!class_exists("GetCustomerAddressesResponse",false)) {
/**
 * GetCustomerAddressesResponse
 */
class GetCustomerAddressesResponse extends BasicResponse {
	/**
	 * @access public
	 * @var tnsGetCustomerAddressesRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var ArrayOfCustomerAddress
	 */
	public $Addresses;
}}

if (!class_exists("GetCustomerAddressesRejectionCode",false)) {
/**
 * GetCustomerAddressesRejectionCode
 */
class GetCustomerAddressesRejectionCode {
}}

if (!class_exists("CustomerAddress",false)) {
/**
 * CustomerAddress
 */
class CustomerAddress {
	/**
	 * @access public
	 * @var sstring
	 */
	public $LegalName;
	/**
	 * @access public
	 * @var sstring
	 */
	public $SecurityNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $PhoneNumber;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressLine1;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressLine2;
	/**
	 * @access public
	 * @var sint
	 */
	public $Postcode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Postarea;
	/**
	 * @access public
	 * @var tnsBusinessTypeCode
	 */
	public $BusinessType;
	/**
	 * @access public
	 * @var sstring
	 */
	public $AddressSelector;
	/**
	 * @access public
	 * @var sstring
	 */
	public $FirstName;
	/**
	 * @access public
	 * @var sstring
	 */
	public $LastName;
}}

if (!class_exists("Ping",false)) {
/**
 * Ping
 */
class Ping {
}}

if (!class_exists("PingResponse",false)) {
/**
 * PingResponse
 */
class PingResponse {
	/**
	 * @access public
	 * @var sstring
	 */
	public $PingResult;
}}

if (!class_exists("GetPaymentPlanParams",false)) {
/**
 * GetPaymentPlanParams
 */
class GetPaymentPlanParams {
	/**
	 * @access public
	 * @var GetPaymentPlanParamsRequest
	 */
	public $request;
}}

if (!class_exists("GetPaymentPlanParamsRequest",false)) {
/**
 * GetPaymentPlanParamsRequest
 */
class GetPaymentPlanParamsRequest extends BasicRequest {
}}

if (!class_exists("GetPaymentPlanParamsResponse",false)) {
/**
 * GetPaymentPlanParamsResponse
 */
class GetPaymentPlanParamsResponse extends BasicResponse {
	/**
	 * @access public
	 * @var ArrayOfCampainCodeInfo
	 */
	public $CampainCodes;
}}

if (!class_exists("CampainCodeInfo",false)) {
/**
 * CampainCodeInfo
 */
class CampainCodeInfo {
	/**
	 * @access public
	 * @var slong
	 */
	public $CampainCode;
	/**
	 * @access public
	 * @var sstring
	 */
	public $Description;
	/**
	 * @access public
	 * @var tnsPaymentPlanTypeCode
	 */
	public $PaymentPlanType;
	/**
	 * @access public
	 * @var sint
	 */
	public $ContractLengthInMonths;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $MonthlyAnnuityFactor;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InitialFee;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $NotificationFee;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $InterestRatePercent;
	/**
	 * @access public
	 * @var sint
	 */
	public $NrOfInterestFreeMonths;
	/**
	 * @access public
	 * @var sint
	 */
	public $NrOfPaymentFreeMonths;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $FromAmount;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $ToAmount;
}}

if (!class_exists("OrderRequest",false)) {
/**
 * OrderRequest
 */
class OrderRequest extends BasicRequest {
	/**
	 * @access public
	 * @var ClientOrderInfo
	 */
	public $Order;
	/**
	 * @access public
	 * @var ArrayOfClientInvoiceRowInfo
	 */
	public $InvoiceRows;
}}

if (!class_exists("OrderResponse",false)) {
/**
 * OrderResponse
 */
class OrderResponse extends BasicResponse {
	/**
	 * @access public
	 * @var slong
	 */
	public $SveaOrderNr;
	/**
	 * @access public
	 * @var tnsOrderRejectionCode
	 */
	public $RejectionCode;
	/**
	 * @access public
	 * @var sboolean
	 */
	public $WillBuyInvoices;
	/**
	 * @access public
	 * @var slong
	 */
	public $AuthorizeId;
	/**
	 * @access public
	 * @var sdouble
	 */
	public $AuthorizedAmount;
	/**
	 * @access public
	 * @var sdateTime
	 */
	public $ExpirationDate;
	/**
	 * @access public
	 * @var CreditReportCustomer
	 */
	public $ValidCustomer;
}}

if (!class_exists("Service",false)) {
/**
 * Service
 * @author WSDLInterpreter
 */
class Service extends SoapClient {
	/**
	 * Default class map for wsdl=>php
	 * @access private
	 * @var array
	 */
	private static $classmap = array(
		"CreateOrder" => "CreateOrder",
		"OrderRequest" => "OrderRequest",
		"BasicRequest" => "BasicRequest",
		"ClientAuthInfo" => "ClientAuthInfo",
		"ClientOrderInfo" => "ClientOrderInfo",
		"ClientInvoiceRowInfo" => "ClientInvoiceRowInfo",
		"CreateOrderResponse" => "CreateOrderResponse",
		"OrderResponse" => "OrderResponse",
		"BasicResponse" => "BasicResponse",
		"OrderRejectionCode" => "OrderRejectionCode",
		"CreditReportCustomer" => "CreditReportCustomer",
		"BusinessTypeCode" => "BusinessTypeCode",
		"ChangeOrderAmount" => "ChangeOrderAmount",
		"ChangeOrderAmountRequest" => "ChangeOrderAmountRequest",
		"ChangeOrderAmountResponse" => "ChangeOrderAmountResponse",
		"ChangeOrderAmountRejectionCode" => "ChangeOrderAmountRejectionCode",
		"CreateInvoice" => "CreateInvoice",
		"CreateInvoiceRequest" => "CreateInvoiceRequest",
		"ClientInvoiceInfo" => "ClientInvoiceInfo",
		"InvoiceDistributionCode" => "InvoiceDistributionCode",
		"CreateInvoiceResponse" => "CreateInvoiceResponse",
		"InvoiceRejectionCode" => "InvoiceRejectionCode",
		"ChangeOrderInfo" => "ChangeOrderInfo",
		"ChangeOrderInfoRequest" => "ChangeOrderInfoRequest",
		"ChangeOrderInfoResponse" => "ChangeOrderInfoResponse",
		"ChangeOrderInfoRejectionCode" => "ChangeOrderInfoRejectionCode",
		"CloseOrder" => "CloseOrder",
		"CloseOrderRequest" => "CloseOrderRequest",
		"CloseOrderResponse" => "CloseOrderResponse",
		"CloseOrderRejectionCode" => "CloseOrderRejectionCode",
		"GetOrders" => "GetOrders",
		"GetOrdersRequest" => "GetOrdersRequest",
		"GetOrdersResponse" => "GetOrdersResponse",
		"OrderStatus" => "OrderStatus",
		"InvoiceStatus" => "InvoiceStatus",
		"GetOrdersRejectionCode" => "GetOrdersRejectionCode",
		"CheckInternalScoring" => "CheckInternalScoring",
		"CheckInternalScoringRequest" => "CheckInternalScoringRequest",
		"CheckInternalScoringResponse" => "CheckInternalScoringResponse",
		"CheckInternalScoringRejectionCode" => "CheckInternalScoringRejectionCode",
		"CreatePaymentPlan" => "CreatePaymentPlan",
		"CreatePaymentPlanRequest" => "CreatePaymentPlanRequest",
		"ClientPaymentPlanInfo" => "ClientPaymentPlanInfo",
		"CreatePaymentPlanResponse" => "CreatePaymentPlanResponse",
		"CreatePaymentPlanRejectionCode" => "CreatePaymentPlanRejectionCode",
		"GetPaymentPlanStatus" => "GetPaymentPlanStatus",
		"GetPaymentPlanStatusRequest" => "GetPaymentPlanStatusRequest",
		"GetPaymentPlanStatusResponse" => "GetPaymentPlanStatusResponse",
		"GetPaymentPlanStatusRejectionCode" => "GetPaymentPlanStatusRejectionCode",
		"GetPaymentPlanResponseStatus" => "GetPaymentPlanResponseStatus",
		"CancelPaymentPlan" => "CancelPaymentPlan",
		"CancelPaymentPlanRequest" => "CancelPaymentPlanRequest",
		"CancelPaymentPlanResponse" => "CancelPaymentPlanResponse",
		"CancelPaymentPlanRejectionCode" => "CancelPaymentPlanRejectionCode",
		"ApprovePaymentPlan" => "ApprovePaymentPlan",
		"ApprovePaymentPlanRequest" => "ApprovePaymentPlanRequest",
		"ApprovePaymentPlanResponse" => "ApprovePaymentPlanResponse",
		"ApprovePaymentPlanRejectionCode" => "ApprovePaymentPlanRejectionCode",
		"GetPaymentPlanOptions" => "GetPaymentPlanOptions",
		"GetPaymentPlanOptionsRequest" => "GetPaymentPlanOptionsRequest",
		"GetPaymentPlanOptionsResponse" => "GetPaymentPlanOptionsResponse",
		"PaymentPlanOption" => "PaymentPlanOption",
		"PaymentPlanTypeCode" => "PaymentPlanTypeCode",
		"GetContractPdf" => "GetContractPdf",
		"GetContractPdfRequest" => "GetContractPdfRequest",
		"GetContractPdfResponse" => "GetContractPdfResponse",
		"GetContractPdfRejectionCode" => "GetContractPdfRejectionCode",
		"AddToBlockList" => "AddToBlockList",
		"AddToBlockListRequest" => "AddToBlockListRequest",
		"AddToBlockListResponse" => "AddToBlockListResponse",
		"GetAddresses" => "GetAddresses",
		"GetCustomerAddressesRequest" => "GetCustomerAddressesRequest",
		"GetAddressesResponse" => "GetAddressesResponse",
		"GetCustomerAddressesResponse" => "GetCustomerAddressesResponse",
		"GetCustomerAddressesRejectionCode" => "GetCustomerAddressesRejectionCode",
		"CustomerAddress" => "CustomerAddress",
		"Ping" => "Ping",
		"PingResponse" => "PingResponse",
		"GetPaymentPlanParams" => "GetPaymentPlanParams",
		"GetPaymentPlanParamsRequest" => "GetPaymentPlanParamsRequest",
		"GetPaymentPlanParamsResponse" => "GetPaymentPlanParamsResponse",
		"CampainCodeInfo" => "CampainCodeInfo",
	);

	/**
	 * Constructor using wsdl location and options array
	 * @param string $wsdl WSDL location for this service
	 * @param array $options Options for the SoapClient
	 */
	public function __construct($wsdl="http://webservices.sveaekonomi.se/webpay_test/SveaWebPay.asmx?WSDL", $options = array('features' => SOAP_SINGLE_ELEMENT_ARRAYS) ) {
		foreach(self::$classmap as $wsdlClassName => $phpClassName) {
		    if(!isset($options['classmap'][$wsdlClassName])) {
		        $options['classmap'][$wsdlClassName] = $phpClassName;
		    }
		}
		parent::__construct($wsdl, $options);
	}

	/**
	 * Checks if an argument list matches against a valid argument type list
	 * @param array $arguments The argument list to check
	 * @param array $validParameters A list of valid argument types
	 * @return boolean true if arguments match against validParameters
	 * @throws Exception invalid function signature message
	 */
	public function _checkArguments($arguments, $validParameters) {
		$variables = "";
		foreach ($arguments as $arg) {
		    $type = gettype($arg);
		    if ($type == "object") {
		        $type = get_class($arg);
		    }
		    $variables .= "(".$type.")";
		}
		if (!in_array($variables, $validParameters)) {
		    throw new Exception("Invalid parameter types: ".str_replace(")(", ", ", $variables));
		}
		return true;
	}

	/**
	 * Service Call: CreateOrder
	 * Parameter options:
	 * (CreateOrder) parameters
	 * (CreateOrder) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CreateOrderResponse
	 * @throws Exception invalid function signature message
	 */
	public function CreateOrder($mixed = null) {
		$validParameters = array(
			"(CreateOrder)",
			"(CreateOrder)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CreateOrder", $args);
	}


	/**
	 * Service Call: ChangeOrderAmount
	 * Parameter options:
	 * (ChangeOrderAmount) parameters
	 * (ChangeOrderAmount) parameters
	 * @param mixed,... See function description for parameter options
	 * @return ChangeOrderAmountResponse
	 * @throws Exception invalid function signature message
	 */
	public function ChangeOrderAmount($mixed = null) {
		$validParameters = array(
			"(ChangeOrderAmount)",
			"(ChangeOrderAmount)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("ChangeOrderAmount", $args);
	}


	/**
	 * Service Call: CreateInvoice
	 * Parameter options:
	 * (CreateInvoice) parameters
	 * (CreateInvoice) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CreateInvoiceResponse
	 * @throws Exception invalid function signature message
	 */
	public function CreateInvoice($mixed = null) {
		$validParameters = array(
			"(CreateInvoice)",
			"(CreateInvoice)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CreateInvoice", $args);
	}


	/**
	 * Service Call: ChangeOrderInfo
	 * Parameter options:
	 * (ChangeOrderInfo) parameters
	 * (ChangeOrderInfo) parameters
	 * @param mixed,... See function description for parameter options
	 * @return ChangeOrderInfoResponse
	 * @throws Exception invalid function signature message
	 */
	public function ChangeOrderInfo($mixed = null) {
		$validParameters = array(
			"(ChangeOrderInfo)",
			"(ChangeOrderInfo)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("ChangeOrderInfo", $args);
	}


	/**
	 * Service Call: CloseOrder
	 * Parameter options:
	 * (CloseOrder) parameters
	 * (CloseOrder) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CloseOrderResponse
	 * @throws Exception invalid function signature message
	 */
	public function CloseOrder($mixed = null) {
		$validParameters = array(
			"(CloseOrder)",
			"(CloseOrder)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CloseOrder", $args);
	}


	/**
	 * Service Call: GetOrders
	 * Parameter options:
	 * (GetOrders) parameters
	 * (GetOrders) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetOrdersResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetOrders($mixed = null) {
		$validParameters = array(
			"(GetOrders)",
			"(GetOrders)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetOrders", $args);
	}


	/**
	 * Service Call: CheckInternalScoring
	 * Parameter options:
	 * (CheckInternalScoring) parameters
	 * (CheckInternalScoring) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CheckInternalScoringResponse
	 * @throws Exception invalid function signature message
	 */
	public function CheckInternalScoring($mixed = null) {
		$validParameters = array(
			"(CheckInternalScoring)",
			"(CheckInternalScoring)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CheckInternalScoring", $args);
	}


	/**
	 * Service Call: CreatePaymentPlan
	 * Parameter options:
	 * (CreatePaymentPlan) parameters
	 * (CreatePaymentPlan) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CreatePaymentPlanResponse
	 * @throws Exception invalid function signature message
	 */
	public function CreatePaymentPlan($mixed = null) {
		$validParameters = array(
			"(CreatePaymentPlan)",
			"(CreatePaymentPlan)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CreatePaymentPlan", $args);
	}


	/**
	 * Service Call: GetPaymentPlanStatus
	 * Parameter options:
	 * (GetPaymentPlanStatus) parameters
	 * (GetPaymentPlanStatus) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetPaymentPlanStatusResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetPaymentPlanStatus($mixed = null) {
		$validParameters = array(
			"(GetPaymentPlanStatus)",
			"(GetPaymentPlanStatus)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetPaymentPlanStatus", $args);
	}


	/**
	 * Service Call: CancelPaymentPlan
	 * Parameter options:
	 * (CancelPaymentPlan) parameters
	 * (CancelPaymentPlan) parameters
	 * @param mixed,... See function description for parameter options
	 * @return CancelPaymentPlanResponse
	 * @throws Exception invalid function signature message
	 */
	public function CancelPaymentPlan($mixed = null) {
		$validParameters = array(
			"(CancelPaymentPlan)",
			"(CancelPaymentPlan)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("CancelPaymentPlan", $args);
	}


	/**
	 * Service Call: ApprovePaymentPlan
	 * Parameter options:
	 * (ApprovePaymentPlan) parameters
	 * (ApprovePaymentPlan) parameters
	 * @param mixed,... See function description for parameter options
	 * @return ApprovePaymentPlanResponse
	 * @throws Exception invalid function signature message
	 */
	public function ApprovePaymentPlan($mixed = null) {
		$validParameters = array(
			"(ApprovePaymentPlan)",
			"(ApprovePaymentPlan)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("ApprovePaymentPlan", $args);
	}


	/**
	 * Service Call: GetPaymentPlanOptions
	 * Parameter options:
	 * (GetPaymentPlanOptions) parameters
	 * (GetPaymentPlanOptions) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetPaymentPlanOptionsResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetPaymentPlanOptions($mixed = null) {
		$validParameters = array(
			"(GetPaymentPlanOptions)",
			"(GetPaymentPlanOptions)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetPaymentPlanOptions", $args);
	}


	/**
	 * Service Call: GetContractPdf
	 * Parameter options:
	 * (GetContractPdf) parameters
	 * (GetContractPdf) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetContractPdfResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetContractPdf($mixed = null) {
		$validParameters = array(
			"(GetContractPdf)",
			"(GetContractPdf)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetContractPdf", $args);
	}


	/**
	 * Service Call: AddToBlockList
	 * Parameter options:
	 * (AddToBlockList) parameters
	 * (AddToBlockList) parameters
	 * @param mixed,... See function description for parameter options
	 * @return AddToBlockListResponse
	 * @throws Exception invalid function signature message
	 */
	public function AddToBlockList($mixed = null) {
		$validParameters = array(
			"(AddToBlockList)",
			"(AddToBlockList)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("AddToBlockList", $args);
	}


	/**
	 * Service Call: GetAddresses
	 * Parameter options:
	 * (GetAddresses) parameters
	 * (GetAddresses) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetAddressesResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetAddresses($mixed = null) {
		$validParameters = array(
			"(GetAddresses)",
			"(GetAddresses)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetAddresses", $args);
	}


	/**
	 * Service Call: Ping
	 * Parameter options:
	 * (Ping) parameters
	 * (Ping) parameters
	 * @param mixed,... See function description for parameter options
	 * @return PingResponse
	 * @throws Exception invalid function signature message
	 */
	public function Ping($mixed = null) {
		$validParameters = array(
			"(Ping)",
			"(Ping)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("Ping", $args);
	}


	/**
	 * Service Call: GetPaymentPlanParams
	 * Parameter options:
	 * (GetPaymentPlanParams) parameters
	 * (GetPaymentPlanParams) parameters
	 * @param mixed,... See function description for parameter options
	 * @return GetPaymentPlanParamsResponse
	 * @throws Exception invalid function signature message
	 */
	public function GetPaymentPlanParams($mixed = null) {
		$validParameters = array(
			"(GetPaymentPlanParams)",
			"(GetPaymentPlanParams)",
		);
		$args = func_get_args();
		$this->_checkArguments($args, $validParameters);
		return $this->__soapCall("GetPaymentPlanParams", $args);
	}


}}

?>