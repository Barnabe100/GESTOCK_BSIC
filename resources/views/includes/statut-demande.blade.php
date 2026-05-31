@if ($demande->statut == 'En attente supérieur')
    <span class="badge bg-warning">

        En attente supérieur

    </span>
@elseif($demande->statut == 'Rejetée')
    <span class="badge bg-danger">

        Rejetée supérieur

    </span>
@elseif($demande->statut == 'En attente gestionnaire')
    <span class="badge bg-info">

        En attente gestionnaire

    </span>
@elseif($demande->statut == 'Rejetée gestionnaire')
    <span class="badge bg-danger">

        Rejetée gestionnaire

    </span>
@elseif($demande->statut == 'En attente validation finale')
    <span class="badge bg-dark">

        En attente validation finale

    </span>
@elseif($demande->statut == 'En attente livraison')
    <span class="badge bg-primary">

        En attente livraison

    </span>
@elseif($demande->statut == 'En attente réception demandeur')
    <span class="badge bg-secondary">

        En attente réception demandeur

    </span>
@elseif($demande->statut == 'Livrée - Demande clôturée')
    <span class="badge bg-success">

        Livrée - Demande clôturée

    </span>
@else
    <span class="badge bg-light text-dark">

        {{ $demande->statut }}

    </span>
@endif
