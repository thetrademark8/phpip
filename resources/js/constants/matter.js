// Filter field categories
export const FILTER_FIELDS = {
  text: [
    'Ref', 'Cat', 'Status', 'Client', 'ClRef', 
    'Applicant', 'Agent', 'AgtRef', 'Title', 
    'Inventor1', 'FilNo', 'PubNo', 'GrtNo', 'responsible'
  ],
  boolean: ['Ctnr', 'include_dead'],
  date: ['Status_date', 'Filed', 'Published', 'Granted'],
  other: ['display_with', 'sortkey', 'sortdir', 'tab']
}

// Label mappings for display
export const FILTER_LABEL_MAP = {
  'Ctnr': 'Containers',
  'include_dead': 'Dead matters',
  'responsible': 'Responsible',
  'Cat': 'Category',
  'Ref': 'Reference',
  'ClRef': 'Client Ref',
  'AgtRef': 'Agent Ref',
  'FilNo': 'Filing No',
  'PubNo': 'Pub. No',
  'GrtNo': 'Grant No',
  'Inventor1': 'Inventor',
  'Status_date': 'Status Date'
}

// Category variants for badges
export const CATEGORY_VARIANTS = {
  'PAT': 'default',
  'TM': 'secondary',
  'DES': 'outline'
}

// Default filter values
export const DEFAULT_FILTERS = {
  // Boolean fields
  Ctnr: false,
  include_dead: false,
  
  // Core fields
  tab: 0,
  display_with: '',
  sortkey: 'id',
  sortdir: 'desc',
  
  // Text fields
  Ref: '',
  Cat: '',
  Status: '',
  Client: '',
  ClRef: '',
  Applicant: '',
  Agent: '',
  AgtRef: '',
  Title: '',
  Inventor1: '',
  responsible: '',
  
  // Date fields
  Status_date: '',
  Filed: '',
  FilNo: '',
  Published: '',
  PubNo: '',
  Granted: '',
  GrtNo: '',
}