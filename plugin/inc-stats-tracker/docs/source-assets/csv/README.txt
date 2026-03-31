TEMPORARY RUNTIME LOCATION — IMPORT CSVs
========================================

This folder is a staging area for CSV files used by the Historical Data Import
feature in the WordPress admin (INC Stats > Import / Export).

CANONICAL SOURCE FILES
----------------------
The authoritative CSV files are maintained at the project root:

    docs/source-assets/csv/

Do not edit or commit the CSV files placed in this folder.

WORKFLOW
--------
1. Copy the CSV files from the project-root docs/source-assets/csv/ folder
   into this folder before running the importer.
2. Run the Historical Data Import in WordPress admin.
3. Remove the CSV files from this folder when the import is complete.

GITIGNORE
---------
*.csv files in this folder are gitignored and must not be committed to version
control. Only .gitkeep and this README.txt are tracked.
