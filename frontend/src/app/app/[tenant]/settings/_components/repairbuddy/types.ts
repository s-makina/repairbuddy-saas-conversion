export type RepairBuddyGeneralSettings = {
  businessName: string;
  businessPhone: string;
  businessAddress: string;
  logoUrl: string;
  email: string;
  caseNumberPrefix: string;
  caseNumberLength: number;
  emailCustomer: boolean;
  attachPdf: boolean;
  nextServiceDateEnabled: boolean;
  gdprAcceptanceText: string;
  gdprLinkLabel: string;
  gdprLinkUrl: string;
  defaultCountry: string;
  disableStatusCheckBySerial: boolean;
};

export type RepairBuddyCurrencySettings = {
  currency: string;
  currencyPosition: "left" | "right" | "left_space" | "right_space";
  thousandSeparator: string;
  decimalSeparator: string;
  numberOfDecimals: number;
};

export type RepairBuddyInvoicesReportsSettings = {
  addQrCodeToInvoice: boolean;
  invoiceFooterMessage: string;
  invoicePrintType: "standard" | "thermal";
  displayPickupDate: boolean;
  displayDeliveryDate: boolean;
  displayNextServiceDate: boolean;
  invoiceDisclaimerTerms: string;
  repairOrderType: "standard" | "detailed";
  termsUrl: string;
  repairOrderPrintSize: "a4" | "letter";
  displayBusinessAddressDetails: boolean;
  displayCustomerEmailAddressDetails: boolean;
  repairOrderFooterMessage: string;
};

export type RepairBuddyJobStatus = {
  id: string;
  name: string;
  slug: string;
  description: string;
  invoiceLabel: string;
  manageWooStock: boolean;
  status: "active" | "inactive";
};

export type RepairBuddyJobStatusesSettings = {
  statuses: RepairBuddyJobStatus[];
  completedStatusId: string;
  cancelledStatusId: string;
};

export type RepairBuddyPaymentStatus = {
  id: string;
  name: string;
  slug: string;
  description: string;
  status: "active" | "inactive";
};

export type RepairBuddyPaymentsSettings = {
  statuses: RepairBuddyPaymentStatus[];
  paymentMethods: {
    cash: boolean;
    card: boolean;
    bankTransfer: boolean;
    paypal: boolean;
    other: boolean;
  };
};

export type RepairBuddyReviewsSettings = {
  requestFeedbackBySms: boolean;
  requestFeedbackByEmail: boolean;
  feedbackPage: string;
  sendReviewRequestIfJobStatusId: string;
  autoFeedbackRequestIntervalDays: number;
  emailSubject: string;
  emailMessageTemplate: string;
  smsMessageTemplate: string;
};

export type RepairBuddyEstimatesSettings = {
  customerEmailSubject: string;
  customerEmailBody: string;
  disableEstimates: boolean;
  bookingQuoteSendToJobs: boolean;
  adminApproveRejectEmailSubject: string;
  adminApproveRejectEmailBody: string;
};

export type RepairBuddyMyAccountSettings = {
  disableBooking: boolean;
  disableEstimates: boolean;
  disableReviews: boolean;
  bookingFormType: "simple" | "detailed";
};

export type RepairBuddyAdditionalDeviceField = {
  id: string;
  label: string;
  type: "text";
  displayInBookingForm: boolean;
  displayInInvoice: boolean;
  displayForCustomer: boolean;
};

export type RepairBuddyDevicesBrandsSettings = {
  enablePinCodeField: boolean;
  showPinCodeInDocuments: boolean;
  useWooProductsAsDevices: boolean;
  labels: {
    note: string;
    pin: string;
    device: string;
    deviceBrand: string;
    deviceType: string;
    imei: string;
  };
  additionalDeviceFields: RepairBuddyAdditionalDeviceField[];
  pickupDeliveryEnabled: boolean;
  pickupCharge: string;
  deliveryCharge: string;
  rentalEnabled: boolean;
  rentalPerDay: string;
  rentalPerWeek: string;
};

export type RepairBuddyPagesSetupSettings = {
  dashboardPage: string;
  statusCheckPage: string;
  feedbackPage: string;
  bookingPage: string;
  servicesPage: string;
  partsPage: string;
  redirectAfterLogin: string;
  enableRegistration: boolean;
};

export type RepairBuddySmsGateway = "twilio" | "nexmo" | "custom";

export type RepairBuddySmsSettings = {
  activateSmsForSelectiveStatuses: boolean;
  gateway: RepairBuddySmsGateway;
  gatewayAccountSid: string;
  gatewayAuthToken: string;
  gatewayFromNumber: string;
  sendWhenStatusChangedToIds: string[];
  testNumber: string;
  testMessage: string;
};

export type RepairBuddyTax = {
  id: string;
  name: string;
  ratePercent: number;
  status: "active" | "inactive";
};

export type RepairBuddyTaxesSettings = {
  enableTaxes: boolean;
  taxes: RepairBuddyTax[];
  defaultTaxId: string;
  invoiceAmounts: "exclusive" | "inclusive";
};

export type RepairBuddyServiceSettings = {
  sidebarDescription: string;
  disableBookingOnServicePage: boolean;
  bookingFormType: "simple" | "detailed";
};

export type RepairBuddyTimeLogsSettings = {
  disableTimeLog: boolean;
  defaultTaxIdForHours: string;
  enableTimeLogForStatusIds: string[];
  activities: string;
};

export type RepairBuddyMaintenanceReminder = {
  id: string;
  name: string;
  intervalDays: number;
  status: "active" | "inactive";
};

export type RepairBuddyMaintenanceRemindersSettings = {
  reminders: RepairBuddyMaintenanceReminder[];
};

export type RepairBuddyStylingLabelsSettings = {
  labels: {
    delivery: string;
    pickup: string;
    nextService: string;
    caseNumber: string;
  };
  colors: {
    primary: string;
    secondary: string;
  };
};

export type RepairBuddySignatureWorkflowChannelTemplates = {
  emailSubject: string;
  emailTemplate: string;
  smsText: string;
};

export type RepairBuddySignatureWorkflowSettings = {
  pickup: {
    enabled: boolean;
    triggerStatusId: string;
    templates: RepairBuddySignatureWorkflowChannelTemplates;
    statusAfterSubmissionId: string;
  };
  delivery: {
    enabled: boolean;
    triggerStatusId: string;
    templates: RepairBuddySignatureWorkflowChannelTemplates;
    statusAfterSubmissionId: string;
  };
};

export type RepairBuddyBookingSettings = {
  customerEmailSubject: string;
  customerEmailBody: string;
  adminEmailSubject: string;
  adminEmailBody: string;
  sendBookingQuoteToJobs: boolean;
  turnOffOtherDeviceBrand: boolean;
  turnOffOtherService: boolean;
  turnOffServicePrice: boolean;
  turnOffIdImeiInBooking: boolean;
  defaultType: string;
  defaultBrand: string;
  defaultDevice: string;
};

export type RepairBuddySettingsDraft = {
  general: RepairBuddyGeneralSettings;
  currency: RepairBuddyCurrencySettings;
  invoicesReports: RepairBuddyInvoicesReportsSettings;
  jobStatuses: RepairBuddyJobStatusesSettings;
  payments: RepairBuddyPaymentsSettings;
  reviews: RepairBuddyReviewsSettings;
  estimates: RepairBuddyEstimatesSettings;
  myAccount: RepairBuddyMyAccountSettings;
  devicesBrands: RepairBuddyDevicesBrandsSettings;
  pagesSetup: RepairBuddyPagesSetupSettings;
  sms: RepairBuddySmsSettings;
  taxes: RepairBuddyTaxesSettings;
  serviceSettings: RepairBuddyServiceSettings;
  timeLogs: RepairBuddyTimeLogsSettings;
  maintenanceReminders: RepairBuddyMaintenanceRemindersSettings;
  stylingLabels: RepairBuddyStylingLabelsSettings;
  signatureWorkflow: RepairBuddySignatureWorkflowSettings;
  booking: RepairBuddyBookingSettings;
};
