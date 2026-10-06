cd src/app
mkdir -p Modules/Evaluation/{Domain/{Entity,ValueObject,Repository},Application/UseCase,Infrastructure/{Persistence,Database/Migrations,Cli},UI/{Controllers,Views},Config}
find Modules -type d -empty -exec touch {}/.gitkeep \;