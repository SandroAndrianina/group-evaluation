mkdir -p src/writable/import
mv ~/Downloads/"Analyse-marketing-S5 - Groupes.csv" src/writable/import/groupes.csv
mv ~/Downloads/"Analyse-marketing-S5 - Données.csv" src/writable/import/donnees.csv
echo "writable/import/*.csv" >> src/.gitignore