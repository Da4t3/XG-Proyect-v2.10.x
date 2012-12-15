<?php

/**
 * @project XG Proyect
 * @version 2.10.x build 0000
 * @copyright Copyright (C) 2008 - 2012
 */

if(!defined('INSIDE')){ die(header("location:../../"));}

	function PlanetResourceUpdate ( $CurrentUser, &$CurrentPlanet, $UpdateTime, $Simul = FALSE )
	{
		global $ProdGrid, $resource, $reslist;

		$game_resource_multiplier		=	read_config ( 'resource_multiplier' );
		$game_metal_basic_income		=	read_config ( 'metal_basic_income' );
		$game_crystal_basic_income		=	read_config ( 'crystal_basic_income' );
		$game_deuterium_basic_income	=	read_config ( 'deuterium_basic_income' );

		$CurrentPlanet['metal_max']		=	(BASE_STORAGE_SIZE + 50000 * (roundUp(pow(1.6,$CurrentPlanet[ $resource[22] ])) -1));
		$CurrentPlanet['crystal_max']	=	(BASE_STORAGE_SIZE + 50000 * (roundUp(pow(1.6,$CurrentPlanet[ $resource[23] ])) -1));
		$CurrentPlanet['deuterium_max']	=	(BASE_STORAGE_SIZE + 50000 * (roundUp(pow(1.6,$CurrentPlanet[ $resource[24] ])) -1));

		$MaxMetalStorage                = $CurrentPlanet['metal_max']     * MAX_OVERFLOW;
		$MaxCristalStorage              = $CurrentPlanet['crystal_max']   * MAX_OVERFLOW;
		$MaxDeuteriumStorage            = $CurrentPlanet['deuterium_max'] * MAX_OVERFLOW;

		$Caps             = array();
		$BuildTemp        = $CurrentPlanet[ 'temp_max' ];

		$parse['production_level'] = 100;

		if ($CurrentPlanet['energy_max'] == 0 && $CurrentPlanet['energy_used'] > 0)
		{
			$post_porcent = 0;
		}
		elseif ($CurrentPlanet['energy_max'] > 0 && ($CurrentPlanet['energy_used'] + $CurrentPlanet['energy_max']) < 0 )
		{
			$post_porcent = floor(($CurrentPlanet['energy_max']) / ($CurrentPlanet['energy_used']*-1) * 100);
		}
		else
		{
			$post_porcent = 100;
		}

		if ($post_porcent > 100)
		{
			$post_porcent = 100;
		}

		for ( $ProdID = 0; $ProdID < 300; $ProdID++ )
		{
			if ( in_array( $ProdID, $reslist['prod']) )
			{
				$BuildLevelFactor = $CurrentPlanet[ $resource[$ProdID]."_porcent" ];
				$BuildLevel = $CurrentPlanet[ $resource[$ProdID] ];
				$Caps['metal_perhour']     += floor( eval ( $ProdGrid[$ProdID]['formule']['metal'] )     * (0.01 * $post_porcent) * ( $game_resource_multiplier ) * ( 1 + ( $CurrentUser['rpg_geologue'] * GEOLOGUE ) ) );
				$Caps['crystal_perhour']   += floor( eval ( $ProdGrid[$ProdID]['formule']['crystal'] )   * (0.01 * $post_porcent) * ( $game_resource_multiplier ) * ( 1 + ( $CurrentUser['rpg_geologue'] * GEOLOGUE ) ) );

				if ($ProdID < 4)
				{
					$Caps['deuterium_perhour'] += floor( eval ( $ProdGrid[$ProdID]['formule']['deuterium'] ) * (0.01 * $post_porcent) * ( $game_resource_multiplier ) * ( 1 + ( $CurrentUser['rpg_geologue'] * GEOLOGUE ) ) );
					$Caps['energy_used']   +=  floor( eval  ( $ProdGrid[$ProdID]['formule']['energy']    ) * ( $game_resource_multiplier ) );
				}
				elseif ($ProdID >= 4 )
				{
					if($ProdID == 12 && $CurrentPlanet['deuterium'] == 0)
						continue;

					$Caps['deuterium_perhour'] += floor( eval ( $ProdGrid[$ProdID]['formule']['deuterium'] ) * (0.01 * $post_porcent) * ( $game_resource_multiplier ) * ( 1 + ( $CurrentUser['rpg_geologue'] * GEOLOGUE ) ) );
					$Caps['energy_max']    +=  floor( eval  ( $ProdGrid[$ProdID]['formule']['energy']    ) * ( $game_resource_multiplier ) * ( 1 + ( $CurrentUser['rpg_ingenieur'] * ENGINEER_ENERGY ) ) );
				}
			}
		}

		if ($CurrentPlanet['planet_type'] == 3)
		{
			$game_metal_basic_income     = 0;
			$game_crystal_basic_income   = 0;
			$game_deuterium_basic_income = 0;
			$CurrentPlanet['metal_perhour']        = 0;
			$CurrentPlanet['crystal_perhour']      = 0;
			$CurrentPlanet['deuterium_perhour']    = 0;
			$CurrentPlanet['energy_used']          = 0;
			$CurrentPlanet['energy_max']           = 0;
		}
		else
		{
			$CurrentPlanet['metal_perhour']        = $Caps['metal_perhour'];
			$CurrentPlanet['crystal_perhour']      = $Caps['crystal_perhour'];
			$CurrentPlanet['deuterium_perhour']    = $Caps['deuterium_perhour'];
			$CurrentPlanet['energy_used']          = $Caps['energy_used'];
			$CurrentPlanet['energy_max']           = $Caps['energy_max'];
		}

		$ProductionTime               = ($UpdateTime - $CurrentPlanet['last_update']);
		$CurrentPlanet['last_update'] = $UpdateTime;

		if ($CurrentPlanet['energy_max'] == 0)
		{
			$CurrentPlanet['metal_perhour']     = $game_metal_basic_income;
			$CurrentPlanet['crystal_perhour']   = $game_crystal_basic_income;
			$CurrentPlanet['deuterium_perhour'] = $game_deuterium_basic_income;
			$production_level            = 100;
		}
		elseif ($CurrentPlanet["energy_max"] >= $CurrentPlanet["energy_used"])
		{
			$production_level = 100;
		}
		else
		{
			$production_level = floor(($CurrentPlanet['energy_max'] / $CurrentPlanet['energy_used']) * 100);
		}
		if($production_level > 100)
		{
			$production_level = 100;
		}
		elseif ($production_level < 0)
		{
			$production_level = 0;
		}

		if ( $CurrentPlanet['metal'] <= $MaxMetalStorage )
		{
			$MetalProduction = (($ProductionTime * ($CurrentPlanet['metal_perhour'] / 3600))) * (0.01 * $production_level);
			$MetalBaseProduc = (($ProductionTime * ($game_metal_basic_income / 3600 )));
			$MetalTheorical  = $CurrentPlanet['metal'] + $MetalProduction  +  $MetalBaseProduc;
			if ( $MetalTheorical <= $MaxMetalStorage )
			{
				$CurrentPlanet['metal']  = $MetalTheorical;
			}
			else
			{
				$CurrentPlanet['metal']  = $MaxMetalStorage;
			}
		}

		if ( $CurrentPlanet['crystal'] <= $MaxCristalStorage )
		{
			$CristalProduction = (($ProductionTime * ($CurrentPlanet['crystal_perhour'] / 3600))) * (0.01 * $production_level);
			$CristalBaseProduc = (($ProductionTime * ($game_crystal_basic_income / 3600 )));
			$CristalTheorical  = $CurrentPlanet['crystal'] + $CristalProduction  +  $CristalBaseProduc;
			if ( $CristalTheorical <= $MaxCristalStorage )
			{
				$CurrentPlanet['crystal']  = $CristalTheorical;
			}
			else
			{
				$CurrentPlanet['crystal']  = $MaxCristalStorage;
			}
		}

		if ( $CurrentPlanet['deuterium'] <= $MaxDeuteriumStorage )
		{
			$DeuteriumProduction = (($ProductionTime * ($CurrentPlanet['deuterium_perhour'] / 3600))) * (0.01 * $production_level);
			$DeuteriumBaseProduc = (($ProductionTime * ($game_deuterium_basic_income / 3600 )));
			$DeuteriumTheorical  = $CurrentPlanet['deuterium'] + $DeuteriumProduction  +  $DeuteriumBaseProduc;
			if ( $DeuteriumTheorical <= $MaxDeuteriumStorage )
			{
				$CurrentPlanet['deuterium']  = $DeuteriumTheorical;
			}
			else
			{
				$CurrentPlanet['deuterium']  = $MaxDeuteriumStorage;
			}
		}

		if( $CurrentPlanet['metal'] < 0 )
		{
			$CurrentPlanet['metal']  = 0;
		}

		if( $CurrentPlanet['crystal'] < 0 )
		{
			$CurrentPlanet['crystal']  = 0;
		}

		if( $CurrentPlanet['deuterium'] < 0 )
		{
			$CurrentPlanet['deuterium']  = 0;
		}

		if ($Simul == FALSE)
		{
			$Builded          = HandleElementBuildingQueue ( $CurrentUser, $CurrentPlanet, $ProductionTime );

			$QryUpdatePlanet  = "UPDATE {{table}} SET ";
			$QryUpdatePlanet .= "`metal` = '"            . $CurrentPlanet['metal']             ."', ";
			$QryUpdatePlanet .= "`crystal` = '"          . $CurrentPlanet['crystal']           ."', ";
			$QryUpdatePlanet .= "`deuterium` = '"        . $CurrentPlanet['deuterium']         ."', ";
			$QryUpdatePlanet .= "`last_update` = '"      . $CurrentPlanet['last_update']       ."', ";
			$QryUpdatePlanet .= "`b_hangar_id` = '"      . $CurrentPlanet['b_hangar_id']       ."', ";
			$QryUpdatePlanet .= "`metal_perhour` = '"    . $CurrentPlanet['metal_perhour']     ."', ";
			$QryUpdatePlanet .= "`crystal_perhour` = '"  . $CurrentPlanet['crystal_perhour']   ."', ";
			$QryUpdatePlanet .= "`deuterium_perhour` = '". $CurrentPlanet['deuterium_perhour'] ."', ";
			$QryUpdatePlanet .= "`energy_used` = '"      . $CurrentPlanet['energy_used']       ."', ";
			$QryUpdatePlanet .= "`energy_max` = '"       . $CurrentPlanet['energy_max']        ."', ";
			if ( $Builded != '' )
			{
				foreach ( $Builded as $Element => $Count )
				{
					if ($Element <> '')
						$QryUpdatePlanet .= "`". $resource[$Element] ."` = '". $CurrentPlanet[$resource[$Element]] ."', ";
				}
			}
			$QryUpdatePlanet .= "`b_hangar` = '". $CurrentPlanet['b_hangar'] ."' ";
			$QryUpdatePlanet .= "WHERE ";
			$QryUpdatePlanet .= "`id` = '". $CurrentPlanet['id'] ."';";
			doquery($QryUpdatePlanet, 'planets');
		}
	}
?>